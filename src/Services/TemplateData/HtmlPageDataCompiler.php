<?php
/**
 * Created by Joseph Daigle.
 * Date: 2019-04-28
 * Time: 21:20
 */

namespace Kasko\Services\TemplateData;


use Kasko\CMS\CMS;
use Kasko\Http\Model\TemplateDataProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * HtmlPageDataCompiler.
 *
 * Global data compiler for html requests (page templates).
 * Note:
 *  This class loads data based on a user's role.
 *
 * @package Kasko\Services
 */
class HtmlPageDataCompiler implements TemplateDataProviderInterface
{
	/**
	 * @var CMS
	 */
	private $cms;

	/**
	 * @var array container parameters needed by this class (configuration values)
	 */
	private $containerParams;

	/**
	 * HtmlPageDataCompiler constructor.
	 *
	 * @param CMS    $cms
	 * @param string ...$containerParams
	 */
	public function __construct(CMS $cms, array $containerParams = [])
	{
		$this->cms       = $cms;
		$this->containerParams = $containerParams;
	}

	/**
	 * @param Request $request
	 *
	 * @return void
	 */
	public function getTemplateData(Request $request)
	{
		// load data for all users (including anonymous)
		$request->attributes->get('template_data')->put('menus', $this->cms->getMenuMap());
		$request->attributes->get('template_data')->put('reviews', $this->cms->getCustomerReviews());
	}
}