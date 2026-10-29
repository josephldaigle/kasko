<?php

declare(strict_types=1);

namespace Kasko\Command;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Kasko\Entity\CustomerReview;
use Kasko\Repository\CustomerReviewRepository;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Throwable;

/**
 * app:reviews:import — repeatable, idempotent upsert of hand-
 * curated reviews from a YAML seed file into the local
 * `customer_review` table.
 *
 * Design notes:
 *
 *   1. The import NEVER touches Google's API. All content is
 *      read from the local YAML file. The resulting rows are
 *      visible to the public site through the existing
 *      ReviewsService -> CMS -> template_data pipeline, so the
 *      frontend design is unchanged.
 *
 *   2. By convention each seed entry has `external_id: null`,
 *      which keeps the row invisible to the sync command's
 *      diff query (CustomerReviewRepository::findBySourceKeyedByExternalId
 *      filters IS NOT NULL). The sync therefore never updates
 *      or deletes manually-seeded rows. Setting a real Google
 *      `reviewId` in the YAML opts a row into sync management
 *      from the next sync run forward.
 *
 *   3. Idempotency. The command classifies each entry as
 *      INSERT, UPDATE, or UNCHANGED by comparing every stored
 *      field. Re-running the import with the YAML unchanged
 *      performs zero writes.
 *
 *   4. Writes run in a single transaction via
 *      EntityManager::wrapInTransaction. A validation failure
 *      or persistence error leaves the DB in its pre-run state.
 *
 *   5. The command is compatible with
 *      `php bin/console app:reviews:sync`. The two commands
 *      operate on disjoint subsets of `customer_review` by
 *      design (import -> external_id IS NULL rows; sync ->
 *      external_id IS NOT NULL rows for a given source), so
 *      the weekly cron can continue to run with no coordination.
 */
#[AsCommand(
    name: 'app:reviews:import',
    description: 'Import hand-curated reviews from a YAML seed file into the local database (idempotent, transactional).',
)]
class ImportReviewsCommand extends Command
{
    private const DEFAULT_RELATIVE_PATH = 'config/reviews/manual_seed.yaml';

    /** @var list<string> */
    private const REQUIRED_FIELDS = [
        'source',
        'reviewer_name',
        'review_text',
        'review_score',
        'review_scale',
        'source_name',
        'external_updated_at',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CustomerReviewRepository $repository,
        private readonly LoggerInterface $logger,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'file',
                null,
                InputOption::VALUE_REQUIRED,
                'Path to the YAML seed file. Relative paths resolve against the project directory. Default: ' . self::DEFAULT_RELATIVE_PATH
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Validate and classify but perform no database writes.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $filePath = $this->resolveFilePath((string) ($input->getOption('file') ?? self::DEFAULT_RELATIVE_PATH));

        $io->section(sprintf('Reviews import: %s', $filePath));

        try {
            $entries = $this->loadEntries($filePath);
        } catch (Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        if ($entries === []) {
            $io->warning('No reviews found in the seed file. Nothing to do.');
            return Command::SUCCESS;
        }

        try {
            $plan = $this->buildPlan($entries, $io);
        } catch (Throwable $e) {
            $this->logger->error('Reviews import validation failed; no DB writes performed', [
                'file'  => $filePath,
                'error' => $e->getMessage(),
            ]);
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        if (!$dryRun) {
            try {
                $this->em->wrapInTransaction(function () use ($plan): void {
                    foreach ($plan['inserts'] as $entity) {
                        $this->em->persist($entity);
                    }
                    // Updates are already managed entities; Doctrine
                    // tracks their mutations and emits UPDATE on flush.
                    $this->em->flush();
                });
            } catch (Throwable $e) {
                $this->logger->error('Reviews import flush failed; transaction rolled back', [
                    'file'  => $filePath,
                    'error' => $e->getMessage(),
                ]);
                $io->error('Database write failed: ' . $e->getMessage() . ' (transaction rolled back; no rows were changed)');
                return Command::FAILURE;
            }
        }

        $io->definitionList(
            ['Processed'  => (string) count($entries)],
            ['Inserted'   => (string) count($plan['inserts'])],
            ['Updated'    => (string) count($plan['updates'])],
            ['Unchanged'  => (string) $plan['unchanged']],
            ['Dry run'    => $dryRun ? 'yes' : 'no'],
        );

        $this->logger->info('Reviews import completed', [
            'file'      => $filePath,
            'processed' => count($entries),
            'inserted'  => count($plan['inserts']),
            'updated'   => count($plan['updates']),
            'unchanged' => $plan['unchanged'],
            'dry_run'   => $dryRun,
        ]);

        return Command::SUCCESS;
    }

    private function resolveFilePath(string $given): string
    {
        if (str_starts_with($given, DIRECTORY_SEPARATOR) || preg_match('#^[A-Za-z]:[\\\\/]#', $given)) {
            return $given;
        }
        return rtrim($this->projectDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $given;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadEntries(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException(sprintf('Seed file not found: %s', $filePath));
        }
        if (!is_readable($filePath)) {
            throw new RuntimeException(sprintf('Seed file is not readable: %s', $filePath));
        }

        try {
            $parsed = Yaml::parseFile($filePath);
        } catch (ParseException $e) {
            throw new RuntimeException(sprintf('Seed file is not valid YAML: %s', $e->getMessage()), previous: $e);
        }

        if (!is_array($parsed) || !isset($parsed['reviews']) || !is_array($parsed['reviews'])) {
            throw new RuntimeException('Seed file must contain a top-level `reviews:` list.');
        }

        return array_values($parsed['reviews']);
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @return array{inserts: list<CustomerReview>, updates: list<CustomerReview>, unchanged: int}
     */
    private function buildPlan(array $entries, SymfonyStyle $io): array
    {
        $inserts = [];
        $updates = [];
        $unchanged = 0;

        foreach ($entries as $index => $entry) {
            $this->validateEntry($entry, $index);

            $externalUpdatedAt = $this->parseTimestamp(
                (string) $entry['external_updated_at'],
                $index
            );

            $source   = (string) $entry['source'];
            $extId    = isset($entry['external_id']) && $entry['external_id'] !== null
                ? (string) $entry['external_id']
                : null;
            $name     = (string) $entry['reviewer_name'];
            $text     = $this->normalizeMultilineText((string) $entry['review_text']);
            $score    = (int) $entry['review_score'];
            $scale    = (int) $entry['review_scale'];
            $city     = isset($entry['city'])   && $entry['city']   !== null ? (string) $entry['city']   : null;
            $state    = isset($entry['state'])  && $entry['state']  !== null ? (string) $entry['state']  : null;
            $srcName  = (string) $entry['source_name'];
            $srcUrl   = isset($entry['source_url']) && $entry['source_url'] !== null
                ? (string) $entry['source_url']
                : null;

            $existing = $extId !== null
                ? $this->repository->findOneBy(['source' => $source, 'externalId' => $extId])
                : $this->repository->findSeedByContent($source, $name, $text);

            if ($existing === null) {
                $entity = new CustomerReview();
                $entity->setSource($source);
                $this->applyFields($entity, $extId, $name, $text, $score, $scale, $city, $state, $srcName, $srcUrl, $externalUpdatedAt);
                $inserts[] = $entity;
                continue;
            }

            if ($this->entityMatches($existing, $extId, $name, $text, $score, $scale, $city, $state, $srcName, $srcUrl, $externalUpdatedAt)) {
                ++$unchanged;
                continue;
            }

            $this->applyFields($existing, $extId, $name, $text, $score, $scale, $city, $state, $srcName, $srcUrl, $externalUpdatedAt);
            $updates[] = $existing;
        }

        return ['inserts' => $inserts, 'updates' => $updates, 'unchanged' => $unchanged];
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function validateEntry(array $entry, int $index): void
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $entry)) {
                throw new RuntimeException(sprintf(
                    'Entry #%d is missing required field `%s`.',
                    $index + 1,
                    $field
                ));
            }
        }

        $score = $entry['review_score'];
        $scale = $entry['review_scale'];
        if (!is_int($score) || !is_int($scale)) {
            throw new RuntimeException(sprintf(
                'Entry #%d: `review_score` and `review_scale` must be integers.',
                $index + 1
            ));
        }
        if ($score < 1 || $score > $scale) {
            throw new RuntimeException(sprintf(
                'Entry #%d: `review_score` (%d) must be between 1 and `review_scale` (%d).',
                $index + 1,
                $score,
                $scale
            ));
        }
    }

    private function parseTimestamp(string $raw, int $index): DateTimeImmutable
    {
        try {
            return (new DateTimeImmutable($raw))->setTimezone(new DateTimeZone('UTC'));
        } catch (Throwable $e) {
            throw new RuntimeException(sprintf(
                'Entry #%d: `external_updated_at` is not a valid date/time: %s',
                $index + 1,
                $raw
            ), previous: $e);
        }
    }

    /**
     * YAML folded block scalars (`>-`) join lines with single
     * spaces. Collapse any residual runs of whitespace so the
     * stored text matches what a human reader of the YAML
     * expects, and so the content-match key for seed rows is
     * stable across cosmetic edits to the file.
     */
    private function normalizeMultilineText(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function applyFields(
        CustomerReview $entity,
        ?string $extId,
        string $name,
        string $text,
        int $score,
        int $scale,
        ?string $city,
        ?string $state,
        string $srcName,
        ?string $srcUrl,
        DateTimeImmutable $externalUpdatedAt,
    ): void {
        $entity
            ->setExternalId($extId)
            ->setReviewerName($name)
            ->setReviewText($text)
            ->setReviewScore($score)
            ->setReviewScale($scale)
            ->setCity($city)
            ->setState($state)
            ->setSourceName($srcName)
            ->setSourceUrl($srcUrl)
            ->setExternalUpdatedAt($externalUpdatedAt);
    }

    private function entityMatches(
        CustomerReview $entity,
        ?string $extId,
        string $name,
        string $text,
        int $score,
        int $scale,
        ?string $city,
        ?string $state,
        string $srcName,
        ?string $srcUrl,
        DateTimeImmutable $externalUpdatedAt,
    ): bool {
        $storedTs = $entity->getExternalUpdatedAt();
        return $entity->getExternalId()   === $extId
            && $entity->getReviewerName() === $name
            && $entity->getReviewText()   === $text
            && $entity->getReviewScore()  === $score
            && $entity->getReviewScale()  === $scale
            && $entity->getCity()         === $city
            && $entity->getState()        === $state
            && $entity->getSourceName()   === $srcName
            && $entity->getSourceUrl()    === $srcUrl
            && $storedTs !== null
            && $storedTs->getTimestamp()  === $externalUpdatedAt->getTimestamp();
    }
}
