<?php

namespace Kasko\Controller;

use Kasko\Entity\FormLead;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class QuotesController extends AbstractController
{
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

		// persist lead
		$entityManager->persist($lead);
		$entityManager->flush();

		try {
			$message = (new Email())
				->from('kas@kaskoconstruction.com')
				->to('kas@kaskoconstruction.com', 'josephldaigle@yahoo.com')
				->subject('New Quote Request - KasKo Construction')
				->html($this->renderView('email/admin/notify-quote-requested.html.twig', ['formLead' => $lead]));
			$mailer->send($message);
		} catch (\Throwable $exception) {
			$logger->error($exception->getMessage(), ['context' => $exception, 'trace' => $exception->getTrace()]);
		}

		return new JsonResponse(['message' => 'You\'ve made a great choice! We will contact you soon to schedule your free quote.'], 200);
	}
}
