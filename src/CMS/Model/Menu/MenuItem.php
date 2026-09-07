<?php
/**
 * Created by Joseph Daigle.
 * Date: 2019-04-26
 * Time: 22:35
 */

namespace Kasko\CMS\Model\Menu;


/**
 * MenuItem.
 *
 * @package Kasko\CMS\Model\Menu
 */
class MenuItem implements MenuItemInterface
{
	/**
	 * @var string
	 */
	private $routeName;

	/**
	 * @var string
	 */
	private $displayText;

	/**
	 * MenuItem constructor.
	 *
	 * @param string $pathName
	 * @param string $displayText
	 */
	public function __construct( string $pathName, string $displayText )
	{
		$this->routeName   = $pathName;
		$this->displayText = $displayText;
	}

	/**
	 * @return string
	 */
	public function getRouteName(): string
	{
		return $this->routeName;
	}

	/**
	 * @return string
	 */
	public function getDisplayText(): string
	{
		return $this->displayText;
	}

}