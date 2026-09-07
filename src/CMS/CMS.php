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
            ->setSourceName('Irvine')
            ->setReviewerLocation('Warner Robins, GA')
            ->setReviewText('This was the best gutter cleaning I had ever had done. Will use again.')
            ->setSourceName('Google')
            ->setSourceUrl('https://goo.gl/maps/PtwEy2SXrMGsr5HG7');

		$reviews[] = (new CustomerReview())
            ->setId(0)
            ->setReviewScore(5)
            ->setReviewScale(5)
            ->setReviewerName('Collena')
            ->setReviewerLocation('Warner Robins, GA')
            ->setReviewText('Clean Gutter Co was very professional, communicative, and prompt. Their work ethic was exceptional. I had just had a bad experience with a different contractor so was kind of leery of hiring another. Joe called me back within an appropriate amount of time, came and gave me a very reasonable estimate and did the work. Bada Bing Bada Boom. I would most definitely recommend them. Great job.')
            ->setSourceName('Google')
            ->setSourceUrl('https://goo.gl/maps/AsWe3UmvEKQcP3dX8');

		return $reviews;
	}
}
