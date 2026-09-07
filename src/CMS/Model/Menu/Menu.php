<?php
/**
 * Created by Joseph Daigle.
 * Date: 2019-04-26
 * Time: 16:09
 */

namespace Kasko\CMS\Model\Menu;


use Ds\Map;


/**
 * Menu.
 *
 * Define a navigation menu.
 *
 * @package Kasko\CMS\Model\Menu
 */
class Menu
{
	/**
	 * @var array
	 */
	private $items;

	/**
	 * Menu constructor.
	 *
	 * @param MenuItemInterface ...$items
	 */
	public function __construct(MenuItemInterface...$items)
	{
		$this->items = new Map();
		$this->loadInitialItems($items);
	}

	/**
	 * @return array
	 */
	public function fetchAllItems()
	{
		return $this->items->toArray();
	}

	/**
	 * Add an item to the menu.
	 *
	 * @param MenuItemInterface $menuItem
	 */
	public function addItem(MenuItemInterface $menuItem)
	{
		$this->items->put($menuItem->getRouteName(), $menuItem);
	}

	/**
	 * Handles loading constructor args into the menu map.
	 *
	 * @param $items
	 */
	protected function loadInitialItems($items)
	{
		foreach($items as $menuItem) {
			$this->addItem($menuItem);
		}
	}
}