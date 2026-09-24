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

		// Services section variants. Each entry drives the "What Are You
		// Looking For?" section (or its variant-specific replacement).
		// Unknown/undefined variants fall back to the default four-card
		// generic fencing grid. Every item defines its own image asset;
		// missing files rely on .fencing-service__image's neutral CSS
		// background-color so the layout stays intact.
		$serviceVariants = [
			'default' => [
				'heading'        => 'What Are You Looking For?',
				'subheading'     => 'From privacy and security to simple repairs, we build fences that fit your property and your goals.',
				'intro'          => null,
				'items'          => [
					[
						'title' => 'Privacy Fencing',
						'copy'  => 'Add security, separation and a finished look to your property.',
						'image' => 'build/images/privacy.jpg',
						'alt'   => 'Privacy fencing',
					],
					[
						'title' => 'Wood Fencing',
						'copy'  => 'Traditional, horizontal or custom wood fences.',
						'image' => 'build/images/wood.jpg',
						'alt'   => 'Wood fencing',
					],
					[
						'title' => 'Chain Link',
						'copy'  => 'Durable, practical fencing for residential and commercial properties.',
						'image' => 'build/images/chain-link.jpg',
						'alt'   => 'Chain link fencing',
					],
					[
						'title' => 'Fence Repair',
						'copy'  => 'Damaged posts, sections, gates and more.',
						'image' => 'build/images/repair.jpg',
						'alt'   => 'Fence repair',
					],
				],
				'closing_prompt' => null,
			],
			'wood' => [
				'heading'    => 'Wood Fencing Built for Your Property',
				'subheading' => 'Privacy. Curb appeal. A fence that actually looks like it belongs there.',
				'intro'      => 'Wood fencing gives homeowners flexibility in both function and appearance. Whether the goal is backyard privacy, a clean property line, or something more architectural, Kasko can help build a fence that fits the property and the project.',
				'items'      => [
					[
						'title' => 'Privacy Fencing',
						'copy'  => 'Full-height wood fencing for backyards, pets, added separation, and a more private outdoor space.',
						'image' => 'build/images/wood-privacy.jpg',
						'alt'   => 'Full-height wood privacy fence',
					],
					[
						'title' => 'Horizontal Fencing',
						'copy'  => 'A clean, contemporary wood-fence option with strong architectural lines.',
						'image' => 'build/images/wood-horizontal.jpg',
						'alt'   => 'Horizontal wood fence',
					],
					[
						'title' => 'Picket & Decorative',
						'copy'  => 'Define the property while keeping the yard more open and visually connected.',
						'image' => 'build/images/wood-picket.jpg',
						'alt'   => 'Picket and decorative wood fence',
					],
					[
						'title' => 'Gates & Custom Details',
						'copy'  => 'Matching gates, transitions, corners, and other details incorporated into the fence installation.',
						'image' => 'build/images/wood-gates.jpg',
						'alt'   => 'Wood fence gate and custom detail',
					],
				],
				'closing_prompt' => [
					'heading'   => 'Not Sure Which Style Makes Sense?',
					'body'      => 'Tell us what you\'re trying to accomplish and we\'ll help you work through the options.',
					'cta_label' => 'Get a Free Quote',
					'cta_href'  => '#quote',
				],
			],
			'privacy' => [
				'heading'    => 'Privacy Fencing Built for Your Backyard',
				'subheading' => 'A private outdoor space. A clear property line. A fence you don\'t have to think about.',
				'intro'      => 'Privacy fencing gives homeowners a real sense of separation from the neighborhood without giving up curb appeal. Kasko installs full-height privacy fences suited to the yard, the goal, and the way you actually use your outdoor space.',
				'items'      => [
					[
						'title' => 'Standard Privacy',
						'copy'  => 'Full-height wood or vinyl privacy fencing for backyards, pools, and everyday separation from the neighbors.',
						'image' => 'build/images/privacy.jpg',
						'alt'   => 'Standard privacy fence',
					],
					[
						'title' => 'Board-on-Board',
						'copy'  => 'Overlapping boards on both sides for maximum privacy, added strength, and a symmetrical look from either yard.',
						'image' => 'build/images/privacy-board-on-board.jpg',
						'alt'   => 'Board-on-board privacy fence',
					],
					[
						'title' => 'Shadowbox',
						'copy'  => 'Alternating boards that let air through while still blocking sightlines — good for larger runs and windy yards.',
						'image' => 'build/images/privacy-shadowbox.jpg',
						'alt'   => 'Shadowbox privacy fence',
					],
					[
						'title' => 'Privacy Gates',
						'copy'  => 'Matching walk-through and drive-through gates sized for how you actually use the yard.',
						'image' => 'build/images/privacy-gates.jpg',
						'alt'   => 'Wood privacy gate',
					],
				],
				'closing_prompt' => [
					'heading'   => 'Not Sure Which Style Fits?',
					'body'      => 'Tell us the yard and how you plan to use it — we\'ll help you land on the right approach before quoting the work.',
					'cta_label' => 'Get a Free Quote',
					'cta_href'  => '#quote',
				],
			],
			'chain-link' => [
				'heading'    => 'Chain-Link Fencing That Does the Job',
				'subheading' => 'Durable, straightforward fencing for yards, dogs, businesses, and property lines.',
				'intro'      => 'Chain-link isn\'t the flashiest fence, but it\'s practical and it lasts. Kasko installs galvanized and vinyl-coated chain-link for residential and commercial properties, sized and gated to how you actually need to use it.',
				'items'      => [
					[
						'title' => 'Residential Chain-Link',
						'copy'  => 'Standard galvanized chain-link for backyards, pet enclosures, and clear property boundaries.',
						'image' => 'build/images/chain-link.jpg',
						'alt'   => 'Residential galvanized chain-link fence',
					],
					[
						'title' => 'Black Coated Chain-Link',
						'copy'  => 'Vinyl-coated chain-link that reads far cleaner against grass and landscaping while adding weather resistance.',
						'image' => 'build/images/chain-link-black.jpg',
						'alt'   => 'Black vinyl-coated chain-link fence',
					],
					[
						'title' => 'Commercial & Security',
						'copy'  => 'Taller runs, heavier gauge, and optional top rails or barbed extensions for businesses, lots, and equipment yards.',
						'image' => 'build/images/chain-link-commercial.jpg',
						'alt'   => 'Commercial chain-link security fence',
					],
					[
						'title' => 'Gates & Access',
						'copy'  => 'Walk-through, double-swing, and rolling gates sized to fit vehicles, equipment, and daily use.',
						'image' => 'build/images/chain-link-gates.jpg',
						'alt'   => 'Chain-link gate',
					],
				],
				'closing_prompt' => [
					'heading'   => 'Not Sure What You Need?',
					'body'      => 'Tell us the property and what the fence is protecting — we\'ll recommend the right height, gauge, and gate setup.',
					'cta_label' => 'Get a Free Quote',
					'cta_href'  => '#quote',
				],
			],
			'repair' => [
				'heading'    => 'Fence Repair, Done Right the First Time',
				'subheading' => 'Leaning posts, broken boards, sagging gates — we fix what\'s actually wrong.',
				'intro'      => 'Not every fence problem needs a full replacement. Kasko repairs damaged sections, resets leaning posts, rehangs gates, and swaps out failed hardware so you get more life out of the fence you already have.',
				'items'      => [
					[
						'title' => 'Leaning & Damaged Fences',
						'copy'  => 'Fences leaning after a storm, root movement, or years of settling — straightened, re-secured, and reinforced where needed.',
						'image' => 'build/images/repair.jpg',
						'alt'   => 'Leaning fence being repaired',
					],
					[
						'title' => 'Post Repair',
						'copy'  => 'Rotten, cracked, or shifting posts replaced without tearing out the whole run.',
						'image' => 'build/images/repair-posts.jpg',
						'alt'   => 'Fence post repair',
					],
					[
						'title' => 'Gate Repair',
						'copy'  => 'Sagging gates rehung, hardware replaced, and hinges reset so they open and close the way they should.',
						'image' => 'build/images/repair-gates.jpg',
						'alt'   => 'Fence gate repair',
					],
					[
						'title' => 'Section Replacement',
						'copy'  => 'Damaged panels or boards swapped in place to match the rest of the fence.',
						'image' => 'build/images/repair-sections.jpg',
						'alt'   => 'Fence section replacement',
					],
				],
				'closing_prompt' => [
					'heading'   => 'Not Sure If It Can Be Repaired?',
					'body'      => 'Send us a couple photos and a short description — we\'ll tell you whether a repair makes sense or if replacement is the smarter call.',
					'cta_label' => 'Get a Free Quote',
					'cta_href'  => '#quote',
				],
			],
		];

		$services = $serviceVariants[$type] ?? $serviceVariants['default'];

		return $this->render('page/fencing.html.twig', [
			'controller_name'      => 'DefaultController',
			'hero'                 => $hero,
			'services'             => $services,
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
