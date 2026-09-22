<?php

namespace Kasko\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailTest extends KernelTestCase
{
	private MailerInterface $mailer;

	protected function setUp(): void
	{
		self::bootKernel();

		$this->mailer = self::getContainer()->get('mailer');
	}

	public function testEmailServiceIsAvailable(): void
	{
		$this->assertInstanceOf(MailerInterface::class, $this->mailer, 'Unexpected mailer type');
		$this->assertTrue(
			method_exists($this->mailer, 'send'),
			'Mailer service is missing a send function.'
		);
	}

	public function testCanSendEmail(): void
	{
		$message = (new Email())
			->from('kas@kaskoconstruction.com')
			->to('josephldaigle@yahoo.com')
			->subject('Test Email')
			->html('<p>This is a test message</p>');

		// With MAILER_DSN=null://null (test/dev default) send() should return without throwing.
		$this->mailer->send($message);
		$this->addToAssertionCount(1);
	}
}
