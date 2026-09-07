<?php
/**
 * Created by Joseph Daigle.
 * Date: 2019-08-04
 * Time: 13:09
 */

namespace Kasko\Services\Marketing;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * InboundLeadRequestFilter.
 *
 * This class filters inbound requests to make sure that lead-producing
 * referrers, like Google Ads, do not encounter 404, or other errors.
 *
 * We want them to receive a default page.
 *
 * @package Kasko\Services\Marketing
 */
class InboundLeadRequestFilter implements EventSubscriberInterface
{
	/**
	 * @inheritdoc
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return [
			KernelEvents::REQUEST => [
				['handleHtmlRequest', 40]
			]
		];
	}


	public function handleHtmlRequest(RequestEvent $event)
	{
		if (! $event->isMasterRequest()) {
			return;
		}

		$request = $event->getRequest();

		// reject requests not having `text/html` accept header
		if (! in_array('text/html', $request->getAcceptableContentTypes())) {
//			$this->logger->warning('Only requests with accept-content header `text/html` should use ' . __CLASS__ . '.');
			return;
		}

	}
}