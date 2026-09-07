<?php
/**
 * Created by Joseph Daigle.
 * Date: 3/10/19
 * Time: 9:37 AM
 */

namespace Kasko\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * EmailTest.
 *
 * @package Kasko\Tests
 */
class EmailTest extends WebTestCase
{
	/**
	 * @var mixed the service being used by the app to send emails
	 */
	private $emailer;

	/**
	 * EmailTest constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		// boot the kernel
		self::bootKernel();

		// fetch emailer service
		$this->emailer = self::$container->get('mailer');
	}

	public function testEmailServiceIsAvailable()
	{
		$this->assertInstanceOf(\Swift_Mailer::class, $this->emailer, 'Unexpected mailer type');
		$this->assertTrue(
			method_exists($this->emailer, 'send'),
			'Mailer service is missing a send function.'
		);
	}

	public function testCanSendEmail(  )
	{
		$message = (new \Swift_Message('Test Email'))
			->setTo('josephldaigle@yahoo.com')
			->setBody('<p>This is a test message</p>');

		$sent = $this->emailer->send($message);

		$this->assertTrue(is_int($sent), 'Unexpected return type from sent()');
		$this->assertTrue($sent === 1, 'Email not sent.');
	}
}