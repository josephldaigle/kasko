<?php
/**
 * Created by Joseph Daigle.
 * Date: 2019-04-27
 * Time: 12:20
 */

namespace Kasko\CMS\Model\Menu;


/**
 * Interface MenuItemInterface.
 *
 * Describe a menu item.
 *
 * @package Kasko\CMS\Model\Menu
 */
interface MenuItemInterface
{
	/**
	 * Get the route name for the menu item.
	 * Corresponds to a configured route for the application.
	 *
	 * @return string
	 */
	public function getRouteName(): string;

	/**
	 * Get the display text for the menu item.
	 *
	 * @return string
	 */
	public function getDisplayText(): string;
}