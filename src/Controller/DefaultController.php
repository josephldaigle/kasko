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
        // Homepage now reuses the service-landing shell (dark hero
        // with a dock photo + the shared quote form on the right).
        // Variables map onto the contract documented in
        // templates/layouts/service-landing.html.twig. `landing_type`
        // carries the canonical `home` service identifier so every
        // lead — including those submitted from the homepage — is
        // attributed to an originating service in the admin email.
        // See QuotesController::SERVICES for the full registry.
        return $this->render('page/home.html.twig', [
            'controller_name' => 'DefaultController',
            'hero_image'      => 'build/images/tyson_dock.png',
            'hero_headline'   => 'Construction & Remodeling in Middle Georgia',
            'hero_subheading' => 'From repairs and renovations to decks, outdoor living spaces, and larger construction projects, KasKo delivers quality work with straightforward communication from start to finish. We proudly serve homeowners in Byron, Warner Robins, Macon, Perry, Fort Valley, and surrounding communities.',
            'landing_type'    => 'home',
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

	/**
	 * /services — overview page of all residential and commercial
	 * offerings. The services grid is driven by the $services array
	 * below so entries can be added, reordered, or edited in one place.
	 * Only entries with a `route` key render as a linked card; the rest
	 * link to the on-page quote section (#quote) for inquiries about
	 * services that don't yet have a dedicated subpage.
	 */
	public function getServices(Request $request)
	{
		$services = [
			[
				'title'    => 'Deck Construction & Repair',
				'copy'     => 'New builds, resurfacing, and repairs to extend the life of an existing deck.',
				'image'    => 'build/images/spires_deck.png',
				'alt'      => 'Residential deck construction',
				'route'    => 'decking',
				'fragment' => 'services',
			],
			[
				'title'    => 'Dock Construction & Repair',
				'copy'     => 'Lakeside and pond dock builds, board replacement, and structural repair.',
				'image'    => 'build/images/parr-dock.png',
				'alt'      => 'Lakeside dock construction',
				'route'    => 'docks',
				'fragment' => 'services',
			],
			[
				'title'    => 'Fencing',
				'copy'     => 'Privacy, wood, and chain-link fencing — installation and repair.',
				'image'    => 'build/images/privacy.jpg',
				'alt'      => 'Fence installation',
				'route'    => 'fencing',
				'fragment' => 'services',
			],
			[
				'title' => 'Painting & Pressure Washing',
				'copy'  => 'Interior and exterior painting plus pressure washing for siding, decks, and drives.',
				'image' => 'build/images/tyson_deck_before_after.png',
				'alt'   => 'Pressure-washed deck before and after',
			],
			[
				'title'    => 'Gutter Cleaning',
				'copy'     => 'Residential and commercial gutter cleaning, including multi-story buildings.',
				'image'    => 'build/images/before-after-house_960X540.jpg',
				'alt'      => 'Residential gutter cleaning',
				'route'    => 'gutter-cleaning',
				'fragment' => 'services',
			],
			// Christmas Light Installation entry temporarily removed
			// from the /services grid. The /christmas-lights route,
			// template, and assets are intentionally preserved so this
			// card can be re-enabled by restoring the array entry.
		];

		return $this->render('page/services.html.twig', [
			'controller_name' => 'DefaultController',
			'services'        => $services,
		]);
	}

	/**
	 * /decking — organic SEO + Google Ads landing page for deck
	 * construction, repair, resurfacing, and related work.
	 *
	 * The page template is a thin wrapper that embeds
	 * `layouts/service-landing.html.twig`. The variables below map to
	 * the layout's documented contract (hero_image, hero_headline,
	 * intro_copy, services, services_heading_*, gallery, etc.) so
	 * copy and content can be edited without changing markup.
	 */
	public function getDecking(Request $request)
	{
		$deckServices = [
			[
				'title' => 'New Deck Construction',
				'copy'  => 'Ground-up builds for single-level and multi-tier decks, sized to the yard and planned around how you actually use the space.',
				'image' => 'build/images/spires_deck.png',
				'alt'   => 'New residential deck construction',
			],
			[
				'title' => 'Deck Repair & Restoration',
				'copy'  => 'Rotten boards, loose fasteners, failing joists, and other structural wear repaired so the deck stays safe underfoot.',
				'image' => 'build/images/deck-repair.jpg',
				'alt'   => 'Deck repair and restoration',
			],
			[
				'title' => 'Deck Resurfacing',
				'copy'  => 'Keep the existing frame and swap in new deck boards for a fresh surface at a fraction of the cost of a full rebuild.',
				'image' => 'build/images/deck-resurfacing.jpg',
				'alt'   => 'Deck resurfacing',
			],
			/*
			 * Temporarily hidden from the /decking services grid.
			 * Definitions, copy, image refs, and alt text are
			 * preserved verbatim — un-comment this block to restore
			 * the three cards.
			[
				'title' => 'Composite Decking',
				'copy'  => 'Low-maintenance composite boards that stand up to Georgia weather without the annual staining a wood deck needs.',
				'image' => null,
				'alt'   => 'Composite decking — photo coming soon',
			],
			[
				'title' => 'Deck Stairs & Railings',
				'copy'  => 'Code-aware stair runs, handrails, and spindles — built to match the deck and feel solid underfoot.',
				'image' => null,
				'alt'   => 'Deck stairs and railings — photo coming soon',
			],
			[
				'title' => 'Deck Inspections',
				'copy'  => 'Pre-purchase and seasonal walkthroughs that flag structural issues before they become safety problems or bigger repair bills.',
				'image' => null,
				'alt'   => 'Deck inspection — photo coming soon',
			],
			*/
		];

		return $this->render('page/decking.html.twig', [
			'controller_name'     => 'DefaultController',
			// Variables consumed by templates/layouts/service-landing.html.twig
			'hero_image'          => 'build/images/spires_deck.png',
			// Horizontal gradient — ~70% dim behind the left text
			// column, fading to transparent before the right form
			// column so the deck photograph stays visible and the
			// quote-card background is not darkened. The fade reaches
			// zero by 65% across the hero, well left of the form
			// column (col-lg-5 begins at ~58% at lg+).
			'hero_overlay'        => 'linear-gradient(to right, rgba(17,17,17,0.70) 0%, rgba(17,17,17,0) 65%)',
			'hero_headline'       => 'Deck Construction & Repair in Middle Georgia',
			// Intro paragraph lives inside the hero (under the H1) via
			// the layout's `hero_subheading` slot — so `intro_copy` is
			// intentionally omitted and the layout skips its separate
			// col-lg-8 intro scaffolding entirely.
			'hero_subheading'     => 'From new deck construction to resurfacing and structural repairs, Kasko builds and restores wood and composite decks, including stairs and railings.',
			'landing_type'        => 'decking',
			'services'            => $deckServices,
			// `services_heading_id` is omitted — the shared layout
			// defaults the H2's id to 'services', matching the
			// site-wide service-section anchor standard.
			'services_heading'    => 'Deck Construction & Repair Services',
			'services_intro'      => 'From ground-up builds to repairs on existing decks, we handle the work homeowners count on for safe, lasting outdoor spaces.',
		]);
	}

	/**
	 * /gutter-cleaning — paid-search landing page for residential,
	 * commercial, and multifamily gutter cleaning. Hero copy, the
	 * services grid, the "Why Choose Kasko" bullets, and the final
	 * CTA copy are plain arrays here so content can be edited without
	 * touching the template. Imagery is placeholder today — the hero
	 * falls through to the dark `.fencing-hero` CSS background and
	 * service tiles render the shared "image coming soon" placeholder.
	 *
	 * The page is scoped to gutter cleaning only — other Kasko services
	 * are intentionally not referenced from this page.
	 */
	public function getGutterCleaning(Request $request)
	{
		$hero = [
			'headline'   => 'Professional Gutter Cleaning in Middle Georgia',
			// Two-paragraph hero description. The layout emits a single
			// <p> for `hero_subheading`, so paragraphs are split into
			// their own variable + override block in the page shell.
			'paragraphs' => [
				'Clogged gutters can lead to overflowing water, damaged fascia, and costly repairs. We remove leaves, debris, and blockages to keep your gutters and downspouts flowing properly.',
				'Serving homeowners, businesses, and apartment communities throughout Middle Georgia.',
			],
		];

		// All service tiles render as "image coming soon" placeholders
		// via the shared layout's `.fencing-service__image--placeholder`
		// branch — the controller signals that by passing `image: null`.
		$gutterServices = [
			[
				'title' => 'Residential Gutter Cleaning',
				'copy'  => 'Removal of leaves, debris, and buildup from residential gutters, including clearing blocked downspouts.',
				'image' => 'build/images/residential-gutter-cleaning.jpeg',
				'alt'   => 'Residential gutter cleaning',
			],
			[
				'title' => 'Commercial & Multifamily',
				'copy'  => 'Professional gutter cleaning for commercial buildings, apartment communities, and managed properties.',
				'image' => 'build/images/skyvac_in_gutter_960X540.jpg',
				'alt'   => 'Professional SkyVac gutter cleaning equipment',
			],
			[
				'title' => 'Gutter Brightening',
				'copy'  => 'Restore the appearance of your gutters with professional exterior cleaning. We remove dirt, stains, and buildup from the outside of your gutters to leave them looking clean and refreshed.',
				'image' => 'build/images/gutter-brightening.jpeg',
				'alt'   => 'Gutter brightening',
			],
		];

		// "Why Choose Kasko" benefits — rendered by the page shell's
		// `service_landing_additional_sections` block as a vertical
		// icon-left list beside a lazy-loaded YouTube video. Each item
		// is {icon, title, copy}; the shell renders title + copy under
		// an icon in forest green.
		$whyChoose = [
			[
				'icon'  => 'fas fa-arrow-down',
				'title' => 'Ground-Level Cleaning',
				'copy'  => 'Professional SkyVac equipment allows us to clean many elevated gutters safely and efficiently from the ground.',
			],
			[
				'icon'  => 'fas fa-water',
				'title' => 'Thorough Results',
				'copy'  => 'We remove accumulated leaves, debris, and blockages from gutters and downspouts.',
			],
			[
				'icon'  => 'fas fa-clipboard-check',
				'title' => 'The Right Approach',
				'copy'  => 'Every property is different. We evaluate access, roof design, and conditions to determine the best cleaning method.',
			],
		];

		return $this->render('page/gutter-cleaning.html.twig', [
			'controller_name'         => 'DefaultController',
			// Flat variables consumed by templates/layouts/service-landing.html.twig.
			'hero_image'              => 'build/images/perry-ga-gutter-cleaning-Jun-2019.jpg',
			'hero_headline'           => $hero['headline'],
			// `hero_paragraphs` is consumed by the page shell's
			// `service_landing_hero_content` override. The shared
			// layout's default single-paragraph `hero_subheading` slot
			// is intentionally left empty here.
			'hero_paragraphs'         => $hero['paragraphs'],
			'landing_type'            => 'gutter-cleaning',
			// Page-scoped wrapper modifier — enables the compact
			// vertical rhythm + no-accent service cards defined in
			// assets/css/parts/_service-landing.scss for this page only.
			'page_wrapper_class'      => 'service-landing--gutter-cleaning',
			'services'                => $gutterServices,
			'services_heading'        => 'Gutter Cleaning Services',
			'services_intro'          => 'From single-family homes to large apartment communities, we provide thorough gutter and downspout cleaning to keep water flowing away from your property.',
			// Consumed by the page shell's `service_landing_additional_sections`
			// override — the shared layout does not read these variables
			// directly.
			'why_choose'              => $whyChoose,
			'why_choose_heading'      => 'Why Choose Kasko for Gutter Cleaning?',
			'why_choose_intro'        => 'We combine specialized equipment with hands-on experience to deliver thorough, efficient gutter cleaning for properties of all sizes.',
			// YouTube video ID for the left column of the "Why Choose"
			// section. Threaded as a plain scalar so the template builds
			// the embed URL + thumbnail URL from a single source.
			'why_choose_video_id'     => 'NFeij4SC6wg',
			// Form overrides scoped to /gutter-cleaning only. Threaded
			// through templates/layouts/service-landing.html.twig into
			// components/forms/quote-request.html.twig; every other
			// page that includes the quote form is byte-identical.
			// `include_description: false` suppresses the project-
			// description textarea; QuotesController already tolerates
			// a missing `project_description` field (defaults to '' and
			// normalizes to null), so no server-side changes are needed.
			// The SSL badge is left as the shared default (rendered) so
			// gutter-cleaning gets the same trust marker as every other
			// landing page.
			'address_placeholder'     => '123 Anywhere Ln, Warner Robins, GA 31080',
			'include_description'     => false,
		]);
	}

	/**
	 * /docks — organic SEO + Google Ads landing page for dock
	 * construction, repair, resurfacing, and routine maintenance.
	 * Follows the same data-driven pattern used by getDecking() /
	 * getGutterCleaning(): hero copy, service grid, and project
	 * gallery are plain arrays here so copy/images can be edited
	 * without touching the template. Gallery entries without an
	 * `image` render as clearly-labeled "photo coming soon"
	 * placeholders.
	 */
	public function getDocks(Request $request)
	{
		$hero = [
			'headline'   => 'Dock Construction & Repair in Middle Georgia',
			'subheading' => 'From new dock construction to resurfacing, structural repairs, and restoration, Kasko builds and maintains waterfront spaces designed for years of enjoyment.',
		];

		$dockServices = [
			[
				'title' => 'New Dock Construction',
				'copy'  => 'Custom wood docks and waterfront structures designed for your property.',
				'image' => 'build/images/tyson_dock.png',
				'alt'   => 'New dock construction on a Middle Georgia lake',
			],
			[
				'title' => 'Dock Repair & Restoration',
				'copy'  => 'Repairs to aging or damaged docks, including structural components and decking.',
				'image' => 'build/images/lucas_dock.png',
				'alt'   => 'Dock repair and restoration',
			],
			[
				'title' => 'Dock Resurfacing',
				'copy'  => 'Replacement of worn or deteriorated decking to restore appearance and usability.',
				'image' => 'build/images/dock-resurfacing.jpg',
				'alt'   => 'Dock resurfacing',
			],
			/*
			 * Temporarily hidden from the /docks services grid.
			 * Definitions, copy, image refs, and alt text are
			 * preserved verbatim — un-comment this block to restore
			 * the three cards.
			[
				'title' => 'Dock Railings & Stairs',
				'copy'  => 'Construction and replacement of dock railings, access stairs, and related features.',
				'image' => null,
				'alt'   => 'Dock railings and stairs — photo coming soon',
			],
			[
				'title' => 'Dock Inspections',
				'copy'  => 'Visual assessments of decking, framing, supports, and visible structural conditions.',
				'image' => null,
				'alt'   => 'Dock inspection — photo coming soon',
			],
			[
				'title' => 'Dock Maintenance',
				'copy'  => 'Routine repairs and preventive maintenance to extend the useful life of existing docks.',
				'image' => null,
				'alt'   => 'Dock maintenance — photo coming soon',
			],
			*/
		];

		return $this->render('page/docks.html.twig', [
			'controller_name'  => 'DefaultController',
			// Flat variables consumed by templates/layouts/service-landing.html.twig
			'hero_image'       => 'build/images/parr-dock.png',
			'hero_headline'    => $hero['headline'],
			'hero_subheading'  => $hero['subheading'],
			'landing_type'     => 'docks',
			'services'         => $dockServices,
			'services_heading' => 'What Does Your Dock Need?',
			'services_intro'   => 'From new builds to repairs on existing docks, we handle the work that keeps waterfront structures safe, functional, and ready for the season.',
		]);
	}

	/**
	 * /christmas-lights — seasonal SEO + Google Ads landing page for
	 * residential and commercial Christmas light installation. Follows
	 * the data-driven pattern used by getDecking() / getDocks() etc:
	 * hero copy, services grid, and pricing tiers are plain arrays so
	 * content can be edited without touching the template. The services
	 * grid intentionally uses only "image coming soon" placeholders
	 * until Kasko-captured installation photos are available.
	 */
	public function getChristmasLights(Request $request)
	{
		$hero = [
			'headline'   => 'Professional Christmas Light Installation in Middle Georgia',
			'subheading' => 'Make your home shine this holiday season. Kasko handles professional Christmas light installation so you can enjoy the holidays without the ladders and hassle.',
		];

		$services = [
			[
				'title' => 'Residential Roofline Lighting',
				'copy'  => 'Clean, evenly spaced roofline installations for single-family homes — hung securely and sized to the house.',
				'alt'   => 'Residential roofline Christmas lighting — image coming soon',
			],
			[
				'title' => 'Commercial Holiday Lighting',
				'copy'  => 'Holiday light installation for storefronts, offices, and commercial properties, including HOA common areas.',
				'alt'   => 'Commercial Christmas lighting — image coming soon',
			],
			[
				'title' => 'Customer-Supplied Light Installation',
				'copy'  => 'Already have lights you love? We\'ll test, hang, and remove the lights you provide.',
				'alt'   => 'Installing customer-supplied Christmas lights — image coming soon',
			],
			[
				'title' => 'Professional-Grade Lighting Supplied by Kasko',
				'copy'  => 'Don\'t want to buy or store lights? Kasko supplies commercial-grade C9 and mini-light product for your display.',
				'alt'   => 'Professional-grade Christmas light product — image coming soon',
			],
			[
				'title' => 'January Light Removal',
				'copy'  => 'When the season is over, we come back to take everything down safely so you don\'t have to climb a ladder in January.',
				'alt'   => 'January Christmas light removal — image coming soon',
			],
		];

		return $this->render('page/christmas-lights.html.twig', [
			'controller_name'         => 'DefaultController',
			// Flat variables consumed by templates/layouts/service-landing.html.twig
			// No hero_image: the layout falls through to the dark
			// `.fencing-hero` CSS background until a Kasko-captured
			// installation photograph replaces this placeholder.
			'hero_headline'           => $hero['headline'],
			'hero_subheading'         => $hero['subheading'],
			'landing_type'            => 'christmas-lights',
			// Opt-in: optional "will you provide the Christmas lights?"
			// field. Opt-out: project-description textarea.
			'include_lights_question' => true,
			'include_description'     => false,
			'services'                => $services,
			'services_heading'        => 'Christmas Light Installation Services',
			'services_intro'          => 'From a straightforward roofline display to full-service installation and January removal, we handle the holiday-lighting work homeowners and property managers in Middle Georgia count on.',
			// No gallery / no additional sections — the layout
			// conditionally skips those blocks.
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
				'headline'   => 'Fence Installation & Repair in Middle Georgia',
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
						// Reuses privacy-gates.jpg — no dedicated wood-gates
						// asset is planned; the gate photo works for both.
						'image' => 'build/images/privacy-gates.jpg',
						'alt'   => 'Wood fence gate and custom detail',
					],
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
						'image' => 'build/images/wood-fence-board-on-board.png',
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
			],
		];

		$services = $serviceVariants[$type] ?? $serviceVariants['default'];

		// On the default /fencing page, each service item links to its
		// matching sub-variant page via `item.href`. The shared
		// layout's services loop renders items with an `href` as full-
		// card `<a>` elements (`.fencing-service--linked`). Variants
		// have no `href` on their items — they're decorative cards.
		$serviceCardRouteTypes = [
			'Privacy Fencing' => 'privacy',
			'Wood Fencing'    => 'wood',
			'Chain Link'      => 'chain-link',
			'Fence Repair'    => 'repair',
		];
		$serviceItems = $services['items'];
		if ($type === null) {
			foreach ($serviceItems as $i => $item) {
				if (isset($serviceCardRouteTypes[$item['title']])) {
					$serviceItems[$i]['href'] = $this->generateUrl(
						'fencing_variant',
						['type' => $serviceCardRouteTypes[$item['title']]]
					);
				}
			}
		}

		// Trust strip: variants show different cues (quality / install
		// / pricing) in place of the site-wide licensed/bonded/comp
		// trio. Pass `trust_items` only on variants; the shared layout
		// falls back to its default trio when `trust_items` is unset.
		$trustItems = null;
		if ($type !== null) {
			$trustItems = [
				['icon' => 'fas fa-cube',   'title' => 'Quality Materials',         'copy' => 'Fences built to last.'],
				['icon' => 'fas fa-hammer', 'title' => 'Professional Installation', 'copy' => 'Clean work. Done right.'],
				['icon' => 'fas fa-tag',    'title' => 'Straightforward Pricing',   'copy' => 'No surprises.'],
			];
		}

		// Services-section header copy. The default page uses an
		// intent-oriented heading rather than the controller's
		// generic "What Are You Looking For?" — keep that override.
		$servicesHeading   = $type === null ? 'What Type of Fence Do You Need?' : $services['heading'];
		$servicesIntro     = $type === null
			? 'From privacy and security to repairs and custom wood fencing, we\'ll help you find the right fit for your property.'
			: $services['subheading'];
		$servicesIntroLong = $type === null ? null : ($services['intro'] ?? null);

		$renderContext = [
			'controller_name'      => 'DefaultController',
			// Flat variables consumed by templates/layouts/service-landing.html.twig
			'hero_image'           => 'build/images/hero.jpg',
			'hero_headline'        => $hero['headline'],
			'hero_subheading'      => $hero['subheading'],
			// Canonical service identifier for lead attribution.
			// Default page → 'fencing'; variants → 'fencing-{variant}'.
			// Keyed against QuotesController::SERVICES so the admin
			// email subject + originating URL resolve correctly.
			'landing_type'         => $type === null ? 'fencing' : 'fencing-' . $type,
			'services'             => $serviceItems,
			'services_heading'     => $servicesHeading,
			'services_intro'       => $servicesIntro,
			'services_intro_long'  => $servicesIntroLong,
			'closing_prompt'       => $services['closing_prompt'] ?? null,
			// Fencing-shell block override for the "KasKo Difference"
			// section needs the type to know whether to render it.
			'fencing_landing_type' => $type,
		];

		if ($trustItems !== null) {
			$renderContext['trust_items'] = $trustItems;
		}

		return $this->render('page/fencing.html.twig', $renderContext);
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
