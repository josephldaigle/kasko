<?php
/**
 * Created by Joseph Daigle.
 * Date: 2019-06-01
 * Time: 23:13
 */

namespace Kasko\CMS;

use Ds\Map;
use Kasko\CMS\Model\Menu\MenuItem;
use Kasko\Services\Reviews\ReviewsService;

/**
 * CMS.
 *
 * Facade for the CMS module.
 *
 * `getCustomerReviews()` previously returned two hardcoded objects
 * built inside this class. The reviews-sync system now populates
 * `customer_review` from Google Business Profile on a weekly cron
 * (see `app:reviews:sync`), and this method delegates to
 * ReviewsService so the DB is the single source of truth. The
 * public shape (array of CustomerReview) and call sites
 * (HtmlPageDataCompiler) are preserved.
 *
 * @package Kasko\CMS
 */
class CMS
{
    public function __construct(
        private readonly ReviewsService $reviewsService,
    ) {
    }

	/**
	 * @return Map
	 */
	public function getMenuMap()
	{
		$menuMap = new Map();
		// compile header menu. "Home" is intentionally omitted — the
		// logo links to the homepage (see components/header/logo.html.twig
		// and the mobile navbar-brand in components/nav-bar.html.twig),
		// so a redundant "Home" nav item is not needed.
		$headerMenu = new Map([
			'services' => new MenuItem('services', 'Services'),
			'about-us' => new MenuItem('about-us', 'About Us'),
			'frequently-asked-questions' => new MenuItem('frequently-asked-questions', 'FAQ'),
			'contact-us' => new MenuItem('contact', 'Contact'),

		]);
		$menuMap->put('header_menu', $headerMenu);

		// compile footer menu
		$footerMenu = new Map([
			'contact-us' => new MenuItem('contact', 'Contact'),
			'terms-of-service' => new MenuItem('terms-of-service', 'Terms of Service'),
			'privacy-policy' => new MenuItem('privacy-policy', 'Privacy Policy')
		]);
		$menuMap->put('footer_menu', $footerMenu);

		return $menuMap;
	}

    /**
     * @return \Kasko\Entity\CustomerReview[]
     */
    public function getCustomerReviews(): array
    {
        return $this->reviewsService->all();
    }
}
