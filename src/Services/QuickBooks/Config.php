<?php
/**
 * Created by Joseph Daigle.
 * Date: 4/21/19
 * Time: 12:46 PM
 */

namespace Kasko\Services\QuickBooks;


use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;


/**
 * Config.
 *
 * Define permissible configuration settings for the QuickBooks API Module.
 *
 * @package Kasko\Services\QuickBooks
 */
class Config implements ConfigurationInterface
{
	/**
	 * @inheritdoc
	 */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder('quickbooks');
		$treeBuilder->root()
			->children()
				->scalarNode('quickbooks_auth_url')
					->isRequired()
					->cannotBeEmpty()
					->end()
				->scalarNode('quickbooks_token_url')
					->isRequired()
					->cannotBeEmpty()
					->end()
				->scalarNode('quickbooks_oauth_scope')
					->isRequired()
					->cannotBeEmpty()
					->end()
				->scalarNode('quickbooks_oauth_redirect_url')
					->isRequired()
					->cannotBeEmpty()
					->end()
				->scalarNode('quickbooks_client_id')
					->isRequired()
					->cannotBeEmpty()
					->end()
				->scalarNode('quickbooks_secret')
					->isRequired()
					->cannotBeEmpty()
					->end()
			->end();

		return $treeBuilder;
	}

}