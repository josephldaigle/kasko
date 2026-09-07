<?php
/**
 * Created by Joseph Daigle.
 * Date: 4/21/19
 * Time: 12:37 PM
 */

namespace Kasko\Services\QuickBooks;


use Symfony\Component\HttpKernel\Bundle\Bundle;


/**
 * QuickBooks.
 *
 * Provides access to
 *
 * @package Kasko\Services\QuickBooks
 */
class QuickBooks extends Bundle
{
	/**
	 * @inheritdoc
	 */
	public function getContainerExtension()
	{
		return new QuickBooksExtension();
	}
}