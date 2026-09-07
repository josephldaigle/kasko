<?php
/**
 * Created by Joseph Daigle.
 * Date: 3/8/19
 * Time: 9:28 PM
 */

namespace Kasko\Controller;

use Kasko\Entity\FormLead;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * QuotesController.
 *
 * @package Kasko\Controller
 */
class QuotesController extends AbstractController
{
	/**
	 * @Route("/api/quotes", name="quotes", methods={"POST"})
	 *
	 * @param Request                $request
	 * @param ValidatorInterface     $validator
	 * @param EntityManagerInterface $entityManager
	 * @param \Swift_Mailer          $mailer
	 * @param LoggerInterface        $logger
	 *
	 * @return JsonResponse
	 */
	public function postFormLead(Request $request, ValidatorInterface $validator, EntityManagerInterface $entityManager, \Swift_Mailer $mailer, LoggerInterface $logger)
	{
		// validate form input

		// create a lead object
		$lead = new FormLead();
		$lead->setName($request->request->get('name'));
		$lead->setEmail($request->request->get('email'));
		$lead->setAddress($request->request->get('address'));
		$lead->setPhoneNumber($request->request->get('phone_number'));

		// persist lead
		$entityManager->persist($lead);
		$entityManager->flush();

		try {
			$message = (new \Swift_Message('New Gutter Quote Request'))
				->setFrom('joe@cleangutterco.com')
				->setTo('joe@cleangutterco.com')
				->setBody($this->renderView('email/admin/notify-quote-requested.html.twig', ['formLead' => $lead]), 'text/html');
			$mailer->send($message);
		} catch(\Exception $exception) {
			$logger->error($exception->getMessage(), ['context' => $exception, 'trace' => $exception->getTrace()]);
		}

		return new JsonResponse(['message' => 'You\'ve made a great choice! We will contact you soon to schedule your free quote.'], 200);
	}
}