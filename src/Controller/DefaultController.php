<?php

namespace Kasko\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * DefaultController.
 *
 * @package Kasko\Controller
 */
class DefaultController extends AbstractController
{
	/**
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \LogicException
	 */
	public function getHome(Request $request)
	{
        return $this->render('page/home.html.twig', [
            'controller_name' => 'DefaultController',
        ]);
	}

	/**
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \LogicException
	 */
	public function getFaq(Request $request)
	{
    $questions = [
      [
        'question' => 'What types of projects does KasKo take on?',
        'answer' => 'We handle remodeling, repairs, decks, outdoor living projects, and a variety of residential construction and improvement work. If you\'re not sure whether your project is a fit, give us a call and tell us what you have in mind.'
      ],
      [
        'question' => 'What areas do you serve?',
        'answer' => 'We serve homeowners throughout Middle Georgia. Project location and travel requirements may affect availability for some jobs.'
      ],
      [
        'question' => 'Do you provide free quotes?',
        'answer' => 'Yes. Contact us with a few details about your project and we\'ll arrange a time to discuss the work and provide a quote.'
      ],
      [
        'question' => 'Are you insured?',
        'answer' => 'Yes. KasKo carries insurance for the work we perform. If you need documentation for your project, just ask.'
      ],
      [
        'question' => 'How soon can you start my project?',
        'answer' => 'Scheduling depends on the size of the project, materials, weather, and our current workload. We\'ll discuss timing with you when we review the job rather than promise a date we can\'t keep.'
      ],
      [
        'question' => 'Who purchases the materials?',
        'answer' => 'KasKo will coordinate the purchase and delivery of all materials needed for the project.'
      ],
      [
        'question' => 'What happens if something changes after work begins?',
        'answer' => 'Construction sometimes reveals conditions that couldn\'t be seen beforehand, and customers occasionally decide to change the scope. If that happens, we\'ll talk with you about the options and any effect on price or schedule before moving forward with additional work.'
      ],
      [
        'question' => 'How do I get started?',
        'answer' => 'Call us or submit the quote form with a few details about your project. We\'ll follow up, talk through what you\'re looking to accomplish, and determine the best next step.'
      ]
    ];

		return $this->render('page/faq.html.twig', [
			'questions' => $questions,
			'controller_name' => 'DefaultController'
		]);
	}

	#[Route('/our-work', name: 'our-work', methods: ['GET'])]
	public function getOurWork(Request $request)
	{
		return $this->render('page/our-work.html.twig', [
			'controller_name' => 'DefaultController'
		]);
	}

	public function getFencing(Request $request, ?string $type = null)
	{
		// Hero copy variants. The `type` argument is populated by Symfony
		// routing from the URL path (see fencing_variant in routes.yaml)
		// and is already restricted to this whitelist by the route's
		// requirements — so any value that reaches this method is either
		// one of the four supported variants or null (the default page).
		$heroVariants = [
			'default' => [
				'headline'   => 'Fencing Built Right.',
				'subheading' => 'Professional fence installation and repair throughout Middle Georgia.',
			],
			'privacy' => [
				'headline'   => 'Privacy Fencing Built Right.',
				'subheading' => 'Quality privacy fencing built for security, separation, and a finished backyard.',
			],
			'wood' => [
				'headline'   => 'Wood Fencing Built Right.',
				'subheading' => 'Professional wood fence installation built to complement your property and last.',
			],
			'chain-link' => [
				'headline'   => 'Chain-Link Fencing Built Right.',
				'subheading' => 'Durable, practical chain-link fencing for residential and commercial properties.',
			],
			'repair' => [
				'headline'   => 'Fence Repair Done Right.',
				'subheading' => 'Professional repairs for damaged posts, gates, sections, and existing fencing.',
			],
		];

		$hero = $heroVariants[$type] ?? $heroVariants['default'];

		return $this->render('page/fencing.html.twig', [
			'controller_name'      => 'DefaultController',
			'hero'                 => $hero,
			'fencing_landing_type' => $type,
		]);
	}

	/**
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \LogicException
	 */
	public function getAbout(Request $request)
	{
		return $this->render('page/about.html.twig', [
			'controller_name' => 'DefaultController'
		]);
	}


	/**
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \LogicException
	 */
	public function getContact(Request $request)
	{
		return $this->render('page/contact.html.twig', [
			'controller_name' => 'DefaultController'
		]);
	}

	/**
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \LogicException
	 */
	public function getTermsOfService( Request $request )
	{
		return $this->render('page/terms-of-service.html.twig', [
			'controller_name' => 'DefaultController'
		]);
	}

	public function getPrivacyPolicy( Request $request )
	{
		return $this->render('page/privacy-policy.html.twig', [
			'controller_name' => 'DefaultController'
		]);
	}
}
