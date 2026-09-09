<?php
/**
 * Created by Joseph Daigle.
 * Date: 3/15/19
 * Time: 6:51 PM
 */

namespace Kasko\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * SeoController.
 *
 * @package Kasko\Controller
 */
class SeoController extends AbstractController
{
	/**
	 * @param Request $request
	 *
	 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
	 */
	public function getSitemapXml( Request $request )
	{
		return $this->file('sitemap.xml', 'Sitemap - www.kaskoconstruction.com', ResponseHeaderBag::DISPOSITION_INLINE);
	}
}