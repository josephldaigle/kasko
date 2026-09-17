<?php
/**
 * Created by Joseph Daigle.
 * Date: 2019-06-01
 * Time: 23:13
 */

namespace Kasko\CMS;

use Kasko\CMS\Model\Menu\MenuItem;
use Kasko\Entity\CustomerReview;
use Ds\Map;

/**
 * CMS.
 *
 * Facade for the CMS module.
 *
 * @package Kasko\CMS
 */
class CMS
{
	/**
	 * @return Map
	 */
	public function getMenuMap()
	{
		$menuMap = new Map();
		// compile header menu
		$headerMenu = new Map([
			'home' => new MenuItem('home', 'Home'),
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
	 * @return array
	 */
	public function getCustomerReviews()
	{
		$reviews = [];
		$reviews[] = (new CustomerReview())
            ->setId(0)
            ->setReviewScore(5)
            ->setReviewScale(5)
            ->setSourceName('Tara')
            ->setReviewerLocation('Perry, GA')
            ->setReviewText('Kasko removed six huge trees from my yard, opening the space just in time for summer. Highly professional and will definitely use again.')
            ->setSourceName('Google')
            ->setSourceUrl('');

		$reviews[] = (new CustomerReview())
            ->setId(0)
            ->setReviewScore(5)
            ->setReviewScale(5)
            ->setReviewerName('Collena')
            ->setReviewerLocation('Warner Robins, GA')
            ->setReviewText('Kasko built a beautiful deck for us. They were very professional and did a great job. I would highly recommend them.')
            ->setSourceName('Google')
            ->setSourceUrl('');

		return $reviews;
	}
}
