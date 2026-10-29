<?php

declare(strict_types=1);

namespace Kasko\Command;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Kasko\Entity\CustomerReview;
use Kasko\Repository\CustomerReviewRepository;
use Kasko\Services\Reviews\ReviewDto;
use Kasko\Services\Reviews\ReviewProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * app:reviews:sync — delta-sync customer reviews from every
 * configured provider into the local database.
 *
 * Guarantees (critical — the whole point of this command):
 *
 *   1. Reviews are retrieved to COMPLETION before any writes.
 *      A single page error, auth error, or exception from the
 *      provider aborts the run for that provider BEFORE INSERT/
 *      UPDATE/DELETE execute. Local rows are never deleted on
 *      the basis of a partial or failed response.
 *
 *   2. Writes for one provider run in a single transaction.
 *      Partial failure during flush rolls back INSERT/UPDATE/
 *      DELETE so the DB is left in its pre-run state.
 *
 *   3. Scope is strict by `source`. A run of the Google
 *      provider never reads, modifies, or deletes rows whose
 *      source is anything other than 'google'. This is why
 *      adding a Yelp provider later needs zero change to this
 *      command.
 *
 *   4. The command is idempotent and safe to run repeatedly.
 *      A no-op run reports every provider's row count as
 *      unchanged and performs no writes.
 */
#[AsCommand(
    name: 'app:reviews:sync',
    description: 'Synchronize customer reviews from all configured providers (default: Google Business Profile).',
)]
class SyncReviewsCommand extends Command
{
    /**
     * @param iterable<ReviewProviderInterface> $providers tagged
     *        app.review_provider, injected by Symfony as a
     *        RewindableGenerator.
     */
    public function __construct(
        private readonly iterable $providers,
        private readonly EntityManagerInterface $em,
        private readonly CustomerReviewRepository $repository,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'provider',
                null,
                InputOption::VALUE_REQUIRED,
                'Sync only the named provider (e.g. "google"). Default: every configured provider.'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Fetch and diff but perform no database writes. Useful for operators verifying credentials or diffs before a real run.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filter = $input->getOption('provider');
        $dryRun = (bool) $input->getOption('dry-run');

        $providers = [];
        foreach ($this->providers as $p) {
            if ($filter !== null && $p->getSourceKey() !== $filter) {
                continue;
            }
            $providers[] = $p;
        }

        if ($providers === []) {
            $io->error(sprintf(
                'No review providers matched%s.',
                $filter !== null ? ' --provider=' . $filter : ''
            ));
            return Command::FAILURE;
        }

        $overallExit = Command::SUCCESS;
        foreach ($providers as $provider) {
            $sourceKey = $provider->getSourceKey();
            $io->section(sprintf('Provider: %s', $sourceKey));

            try {
                $this->syncProvider($provider, $io, $dryRun);
            } catch (Throwable $e) {
                $this->logger->error('Reviews sync failed; local rows preserved', [
                    'provider' => $sourceKey,
                    'error'    => $e->getMessage(),
                ]);
                $io->error(sprintf(
                    '%s sync failed: %s — local rows for this provider were NOT modified.',
                    $sourceKey,
                    $e->getMessage()
                ));
                $overallExit = Command::FAILURE;
            }
        }

        return $overallExit;
    }

    private function syncProvider(ReviewProviderInterface $provider, SymfonyStyle $io, bool $dryRun): void
    {
        $sourceKey = $provider->getSourceKey();

        // STEP 1 — fetch EVERYTHING. Any error here propagates out and
        // the caller aborts the run. No writes have happened yet.
        $incoming = [];
        $retrieved = 0;
        foreach ($provider->fetchAll() as $dto) {
            if (!$this->isDtoValid($dto, $sourceKey, $io)) {
                continue;
            }
            // De-dupe within a single page response — unlikely from
            // Google but a cheap safety net before we trust externalId
            // as a map key.
            $incoming[$dto->externalId] = $dto;
            ++$retrieved;
        }

        // STEP 2 — load the current local state for this provider.
        $existing = $this->repository->findBySourceKeyedByExternalId($sourceKey);

        // STEP 3 — classify.
        $toInsert = [];
        $toUpdate = [];
        $unchanged = 0;
        foreach ($incoming as $externalId => $dto) {
            $local = $existing[$externalId] ?? null;
            if ($local === null) {
                $toInsert[] = $dto;
            } elseif ($this->providerNewer($dto, $local)) {
                $toUpdate[] = [$local, $dto];
            } else {
                ++$unchanged;
            }
        }

        $toDelete = [];
        foreach ($existing as $externalId => $local) {
            if (!isset($incoming[$externalId])) {
                $toDelete[] = $local;
            }
        }

        // STEP 4 — write. Transactional. Nothing above has mutated the DB
        // or raised, so DELETE is only reached after a complete fetch.
        if ($dryRun) {
            $io->note('--dry-run: no database writes performed.');
        } else {
            $this->em->wrapInTransaction(function () use ($toInsert, $toUpdate, $toDelete, $sourceKey): void {
                foreach ($toInsert as $dto) {
                    $this->em->persist($this->newEntityFromDto($dto, $sourceKey));
                }
                foreach ($toUpdate as [$local, $dto]) {
                    $this->applyDto($local, $dto);
                }
                foreach ($toDelete as $local) {
                    $this->em->remove($local);
                }
                $this->em->flush();
            });
        }

        $io->definitionList(
            ['Retrieved' => (string) $retrieved],
            ['Inserted'  => (string) count($toInsert)],
            ['Updated'   => (string) count($toUpdate)],
            ['Deleted'   => (string) count($toDelete)],
            ['Unchanged' => (string) $unchanged],
            ['Dry run'   => $dryRun ? 'yes' : 'no'],
        );

        $this->logger->info('Reviews sync completed', [
            'provider'  => $sourceKey,
            'retrieved' => $retrieved,
            'inserted'  => count($toInsert),
            'updated'   => count($toUpdate),
            'deleted'   => count($toDelete),
            'unchanged' => $unchanged,
            'dry_run'   => $dryRun,
        ]);
    }

    private function isDtoValid(ReviewDto $dto, string $expectedSource, SymfonyStyle $io): bool
    {
        if ($dto->source !== $expectedSource) {
            $io->warning(sprintf(
                'Provider %s yielded a DTO with source=%s; skipping',
                $expectedSource,
                $dto->source
            ));
            return false;
        }
        if ($dto->externalId === '') {
            return false;
        }
        if ($dto->rating < 1 || $dto->rating > $dto->ratingScale) {
            $io->warning(sprintf(
                'Review %s has out-of-range rating %d (scale %d); skipping',
                $dto->externalId,
                $dto->rating,
                $dto->ratingScale,
            ));
            return false;
        }
        return true;
    }

    private function providerNewer(ReviewDto $dto, CustomerReview $local): bool
    {
        $localTs = $local->getExternalUpdatedAt();
        if ($localTs === null) {
            return true;
        }
        return $dto->externalUpdatedAt > $localTs;
    }

    private function newEntityFromDto(ReviewDto $dto, string $sourceKey): CustomerReview
    {
        $entity = new CustomerReview();
        $entity->setSource($sourceKey);
        $this->applyDto($entity, $dto);
        return $entity;
    }

    private function applyDto(CustomerReview $entity, ReviewDto $dto): void
    {
        $entity
            ->setExternalId($dto->externalId)
            ->setReviewerName($dto->author)
            ->setReviewText($dto->reviewText)
            ->setReviewScore($dto->rating)
            ->setReviewScale($dto->ratingScale)
            ->setCity($dto->city)
            ->setState($dto->state)
            ->setSourceName($this->prettySourceName($entity->getSource()))
            ->setSourceUrl($dto->sourceUrl)
            ->setExternalUpdatedAt(
                $dto->externalUpdatedAt->setTimezone(new DateTimeZone('UTC'))
            );
        // Belt-and-suspenders: force updated_at bump even on brand-new
        // entities (the PreUpdate callback only fires on UPDATE).
        $entity->touchUpdatedAt();
    }

    private function prettySourceName(string $sourceKey): string
    {
        return match ($sourceKey) {
            CustomerReview::SOURCE_GOOGLE => 'Google',
            default => ucfirst($sourceKey),
        };
    }
}
