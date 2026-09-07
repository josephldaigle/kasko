<?php
/**
 * Created by Joseph Daigle.
 * Date: 4/21/19
 * Time: 1:01 PM
 */

namespace Kasko\Services\QuickBooks;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;


/**
 * SymfonyExtension.
 *
 * @package Kasko\Services\QuickBooks
 */
class QuickBooksExtension extends Extension
{
	/**
	 * @inheritdoc
	 */
	public function load( array $configs, ContainerBuilder $container )
	{
		// TODO: Implement load() method.
	}

	/**
	 * @inheritdoc
	 */
	public function getAlias()
	{
		return 'quickbooks';
	}
}