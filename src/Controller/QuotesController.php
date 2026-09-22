<?php

namespace Kasko\Controller;

use Kasko\Entity\FormLead;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

	#[Route('/api/quotes', name: 'quotes', methods: ['POST'])]
	public function postFormLead(Request $request, ValidatorInterface $validator, EntityManagerInterface $entityManager, MailerInterface $mailer, LoggerInterface $logger): JsonResponse
	{
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

		$this->sendAdminNotification($mailer, $logger, $lead);
		$this->sendCustomerConfirmation($mailer, $logger, $lead);

		return new JsonResponse(['message' => 'You\'ve made a great choice! We will contact you soon to schedule your free quote.'], 200);
	}

	private function sendAdminNotification(MailerInterface $mailer, LoggerInterface $logger, FormLead $lead): void
	{
		try {
			$message = (new Email())
				->from(self::FROM_ADDRESS)
				->to(...self::ADMIN_ADDRESSES)
				->subject('New Quote Request - KasKo Construction')
				->html($this->renderView('email/admin/notify-quote-requested.html.twig', ['formLead' => $lead]));

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
