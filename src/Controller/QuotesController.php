<?php

namespace Kasko\Controller;

use Kasko\Entity\FormLead;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class QuotesController extends AbstractController
{
	private const FROM_ADDRESS = 'kas@kaskoconstruction.com';

	/**
	 * Recipients of the internal/business quote-request notification.
	 * The customer's confirmation email is sent separately (see
	 * sendCustomerConfirmation) so the customer never receives the
	 * internal notification.
	 */
	private const ADMIN_ADDRESSES = [
		'kas@kaskoconstruction.com',
		'josephldaigle@yahoo.com',
	];

	/**
	 * Canonical registry of originating services for lead attribution.
	 *
	 * Keyed by the identifier that the client-side form posts in the
	 * `landing_type` hidden input. Each entry supplies:
	 *   - `label`:   human-readable service name, rendered in the
	 *                admin notification email body.
	 *   - `route`:   Symfony route name used to reconstruct the
	 *                originating page URL server-side (never trust a
	 *                Referer header or a client-posted URL).
	 *   - `params`:  route parameters for the above.
	 *   - `subject`: admin notification email subject.
	 *
	 * Unknown or missing `landing_type` values fall back to the `home`
	 * entry so the admin always receives a well-formed notification
	 * (subject matches the historical "New Quote Request" default).
	 */
	private const SERVICES = [
		'home' => [
			'label'   => 'Homepage',
			'route'   => 'home',
			'params'  => [],
			'subject' => 'New Quote Request',
		],
		'fencing' => [
			'label'   => 'Fencing',
			'route'   => 'fencing',
			'params'  => [],
			'subject' => 'New Fencing Lead',
		],
		'fencing-privacy' => [
			'label'   => 'Privacy Fencing',
			'route'   => 'fencing_variant',
			'params'  => ['type' => 'privacy'],
			'subject' => 'New Privacy Fencing Lead',
		],
		'fencing-wood' => [
			'label'   => 'Wood Fencing',
			'route'   => 'fencing_variant',
			'params'  => ['type' => 'wood'],
			'subject' => 'New Wood Fencing Lead',
		],
		'fencing-chain-link' => [
			'label'   => 'Chain-Link Fencing',
			'route'   => 'fencing_variant',
			'params'  => ['type' => 'chain-link'],
			'subject' => 'New Chain-Link Fencing Lead',
		],
		'fencing-repair' => [
			'label'   => 'Fence Repair',
			'route'   => 'fencing_variant',
			'params'  => ['type' => 'repair'],
			'subject' => 'New Fence Repair Lead',
		],
		'decking' => [
			'label'   => 'Decks',
			'route'   => 'decking',
			'params'  => [],
			'subject' => 'New Deck Lead',
		],
		'docks' => [
			'label'   => 'Docks',
			'route'   => 'docks',
			'params'  => [],
			'subject' => 'New Dock Lead',
		],
		'gutter-cleaning' => [
			'label'   => 'Gutter Cleaning',
			'route'   => 'gutter-cleaning',
			'params'  => [],
			'subject' => 'New Gutter Cleaning Lead',
		],
		'christmas-lights' => [
			'label'   => 'Christmas Lights',
			'route'   => 'christmas-lights',
			'params'  => [],
			'subject' => 'New Christmas Lights Lead',
		],
	];

	#[Route('/api/quotes', name: 'quotes', methods: ['POST'])]
	public function postFormLead(
		Request $request,
		ValidatorInterface $validator,
		EntityManagerInterface $entityManager,
		MailerInterface $mailer,
		LoggerInterface $logger,
		HttpClientInterface $httpClient,
		#[Autowire(env: 'TURNSTILE_SECRET_KEY')]
		string $turnstileSecretKey,
	): JsonResponse
	{
		// Cloudflare Turnstile verification must succeed before we touch
		// the database or send any email. A missing/invalid token short-
		// circuits the request with a user-facing message.
		$turnstileToken = (string) $request->request->get('cf-turnstile-response', '');
		if ($turnstileToken === '' || !$this->verifyTurnstile($turnstileToken, $request->getClientIp(), $turnstileSecretKey, $httpClient, $logger)) {
			return new JsonResponse(
				['message' => "We couldn't verify your submission. Please try again."],
				400
			);
		}

		// Resolve the originating service from the posted `landing_type`
		// identifier. Unknown/missing values silently fall back to the
		// `home` entry so admin notifications always carry a valid
		// service label + URL. The route name + params come from the
		// server-side registry, so the originating URL is reconstructed
		// here rather than trusting a client-posted URL or Referer.
		$landingType     = (string) $request->request->get('landing_type', '');
		$serviceKey      = isset(self::SERVICES[$landingType]) ? $landingType : 'home';
		$service         = self::SERVICES[$serviceKey];
		$originatingUrl  = $this->generateUrl($service['route'], $service['params'], UrlGeneratorInterface::ABSOLUTE_URL);

		// create a lead object
		$lead = new FormLead();
		$lead->setName($request->request->get('name'));
		$lead->setEmail($request->request->get('email'));
		$lead->setAddress($request->request->get('address'));
		$lead->setPhoneNumber($request->request->get('phone_number'));

		// Optional project description. Empty textareas post as an
		// empty string; store null instead so the email template can
		// cleanly detect "not supplied".
		$projectDescription = trim((string) $request->request->get('project_description', ''));
		$projectDescription = $this->prependServiceDescriptionTag(
			$projectDescription,
			$serviceKey,
			$request
		);
		$lead->setProjectDescription($projectDescription !== '' ? $projectDescription : null);

		// Server-side validation. Only the Length(max: 2000) constraint
		// on project_description is declared today; if the payload is
		// too long, refuse the submission rather than truncate.
		$errors = $validator->validate($lead);
		if (count($errors) > 0) {
			return new JsonResponse(['message' => 'Your submission is invalid. Please review the form and try again.'], 400);
		}

		// persist lead -- must succeed before we attempt any email so a mail
		// outage never causes a submission to be lost.
		$entityManager->persist($lead);
		$entityManager->flush();

		$this->sendAdminNotification($mailer, $logger, $lead, $service, $originatingUrl);
		$this->sendCustomerConfirmation($mailer, $logger, $lead);

		return new JsonResponse(['message' => 'You\'ve made a great choice! We will contact you soon to schedule your free quote.'], 200);
	}

	/**
	 * Preserves the existing project_description tag format for
	 * attribution:
	 *   - Fencing variants:  `[Fencing landing – {variant}] ...`
	 *   - Christmas lights:  `[Christmas lights(... lights_supply note ...)] ...`
	 * Other services add no tag; the service name is carried through
	 * the admin email body (`service_label` + `originating_url`) rather
	 * than being folded into the description column.
	 */
	private function prependServiceDescriptionTag(string $description, string $serviceKey, Request $request): string
	{
		if (str_starts_with($serviceKey, 'fencing-')) {
			$variant = substr($serviceKey, strlen('fencing-'));
			return trim("[Fencing landing – {$variant}] {$description}");
		}

		if ($serviceKey === 'christmas-lights') {
			// Christmas-lights submissions carry an optional
			// `lights_supply` field (customer-provided answer to "will
			// you provide the Christmas lights?"). We fold the answer
			// into the project description tag so it rides along with
			// the existing admin/customer email flow and the DB column
			// without a schema change.
			$lightsSupply = trim((string) $request->request->get('lights_supply', ''));
			$lightsSupplyLabels = [
				'has-lights'   => 'Customer already has lights',
				'kasko-supply' => 'Kasko to supply lights',
				'not-sure'     => 'Not sure yet',
			];
			$supplyNote = isset($lightsSupplyLabels[$lightsSupply])
				? " ({$lightsSupplyLabels[$lightsSupply]})"
				: '';
			return trim("[Christmas lights{$supplyNote}] {$description}");
		}

		return $description;
	}

	private function verifyTurnstile(string $token, ?string $remoteIp, string $secretKey, HttpClientInterface $httpClient, LoggerInterface $logger): bool
	{
		if ($secretKey === '') {
			$logger->error('Turnstile verification skipped: TURNSTILE_SECRET_KEY is not configured');
			return false;
		}

		$body = [
			'secret' => $secretKey,
			'response' => $token,
		];
		if ($remoteIp !== null && $remoteIp !== '') {
			$body['remoteip'] = $remoteIp;
		}

		try {
			$response = $httpClient->request('POST', 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
				'body' => $body,
				'timeout' => 5,
			]);
			$data = $response->toArray(false);
		} catch (\Throwable $exception) {
			$logger->error('Turnstile verification request failed', ['exception' => $exception]);
			return false;
		}

		return isset($data['success']) && $data['success'] === true;
	}

	/**
	 * @param array{label: string, route: string, params: array, subject: string} $service
	 */
	private function sendAdminNotification(MailerInterface $mailer, LoggerInterface $logger, FormLead $lead, array $service, string $originatingUrl): void
	{
		try {
			$message = (new Email())
				->from(self::FROM_ADDRESS)
				->to(...self::ADMIN_ADDRESSES)
				->subject($service['subject'])
				->html($this->renderView('email/admin/notify-quote-requested.html.twig', [
					'formLead'        => $lead,
					'service_label'   => $service['label'],
					'originating_url' => $originatingUrl,
				]));

			$mailer->send($message);
		} catch (\Throwable $exception) {
			$logger->error('Admin notification email failed for quote request', [
				'notification' => 'admin',
				'form_lead_id' => $lead->getId(),
				'exception' => $exception,
			]);
		}
	}

	private function sendCustomerConfirmation(MailerInterface $mailer, LoggerInterface $logger, FormLead $lead): void
	{
		$customerEmail = $lead->getEmail();

		if (empty($customerEmail)) {
			$logger->warning('Customer confirmation email skipped: no customer email on submission', [
				'notification' => 'customer',
				'form_lead_id' => $lead->getId(),
			]);
			return;
		}

		try {
			// TemplatedEmail + both html/text templates -> multipart/alternative.
			// Single-part text/html was arriving empty in some external inboxes
			// because clients sanitizing HTML had no text/plain fallback to fall
			// back to.
			$message = (new TemplatedEmail())
				->from(self::FROM_ADDRESS)
				->to($customerEmail)
				->subject('We received your request | KasKo Construction & Remodeling')
				->htmlTemplate('email/customer/quote-received.html.twig')
				->textTemplate('email/customer/quote-received.txt.twig')
				->context(['formLead' => $lead]);

			$mailer->send($message);
		} catch (\Throwable $exception) {
			$logger->error('Customer confirmation email failed for quote request', [
				'notification' => 'customer',
				'form_lead_id' => $lead->getId(),
				'customer_email' => $customerEmail,
				'exception' => $exception,
			]);
		}
	}
}
