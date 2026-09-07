<?php
/**
 * Created by Joseph Daigle.
 * Date: 2019-04-26
 * Time: 14:15
 */

namespace Kasko\Http\Event;


use Kasko\Http\Model\TemplateDataProviderInterface;
use Ds\Map;
use Psr\Log\LoggerInterface;
use SebastianBergmann\GlobalState\RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;


/**
 * HtmlRequestSubscriber.
 *
 * @package Kasko\Http\Event
 */
class KernelRequestSubscriber implements EventSubscriberInterface
{
	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var CsrfTokenManager
	 */
	private $securityService;

	/**
	 * @var array
	 */
	private $templateDataProviders = [];

	/**
	 * KernelRequestSubscriber constructor.
	 *
	 * @param LoggerInterface               $logger
	 * @param CsrfTokenManager              $securityService
	 * @param TemplateDataProviderInterface ...$templateDataProviders
	 */
	public function __construct(LoggerInterface $logger, CsrfTokenManager $securityService, TemplateDataProviderInterface ...$templateDataProviders)
	{
		$this->logger = $logger;
		$this->securityService = $securityService;
		$this->templateDataProviders = $templateDataProviders;
	}

	/**
	 * @inheritdoc
	 */
	public static function getSubscribedEvents()
	{
		return [
			KernelEvents::REQUEST => [
				['handleHtmlRequest', 0],
				['validateCsrfToken', 1]
			]
		];
	}

	/**
	 * Add page data for requests with accept html header.
	 *
	 * @param RequestEvent $event
	 *
	 * @throws RuntimeException
	 */
	public function handleHtmlRequest(RequestEvent $event)
	{
//		if (! $event->isMasterRequest()) {
//			return;
//		}

		$request = $event->getRequest();

		// reject requests not having `text/html` accept header
//		if (! in_array('text/html', $request->getAcceptableContentTypes())) {
//			return;
//		}

		// initialize template data for request
		if (! $request->attributes->has('template_data')) {
			$request->attributes->set('template_data', new Map());
		}

		if (! $request->attributes->get('template_data') instanceof Map) {
			throw new RuntimeException('The value of template_data in the request is an invalid type.');
		}

		// call registered template data providers
		foreach($this->templateDataProviders as $dataProvider) {
			$dataProvider->getTemplateData($request);
		}

		return;
	}

	/**
	 * Validates csrf tokens for json form requests.
	 *
	 * @param RequestEvent $event
	 *
	 * @throws \Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
	 */
	public function validateCsrfToken(RequestEvent $event)
	{
		if (! $event->isMasterRequest()) {
			return;
		}

		$request = $event->getRequest();

		$id = $request->request->get('form-name', '');
		$token = $request->request->get('token', '');

		// only validate for json POST requests
		if (! ($request->getMethod() === 'POST')) {
			return;
		}

		if (! 0 === strpos($request->headers->get('Content-Type'), 'application/json'))
		{
			$event->setResponse(new JsonResponse(['message' => 'Unaccepted content type for POST request. Can only honor application/json.'], JsonResponse::HTTP_BAD_REQUEST));
			return;
		}

		if (true !== $this->securityService->isTokenValid(new CsrfToken($id, $token)))
		{
			$this->logger->warning('Failed attempt to validate csrf token.', ['id' => $id, 'token' => $token]);

			// bad request, return 400
			$event->setResponse(new JsonResponse(['message' => 'Failed to validate the request: Invalid security token.'], JsonResponse::HTTP_BAD_REQUEST));
			return;
		}

		return;
	}
}