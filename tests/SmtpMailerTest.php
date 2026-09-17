<?php

declare(strict_types=1);

namespace MiGears\Mail\Tests;

use PHPUnit\Framework\TestCase;
use MiGears\Mail\Exception\MailException;
use MiGears\Mail\Mail;
use MiGears\Mail\SmtpMailer;

final class SmtpMailerTest extends TestCase
{
    public function testSendWithoutRecipientsThrowsException(): void
    {
        $mailer = new SmtpMailer('localhost', 25);
        $mail = (new Mail())->withFrom('from@example.com')->withSubject('Test');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('No recipient specified');

        $mailer->send($mail);
    }

    public function testSendWithoutSenderThrowsException(): void
    {
        $mailer = new SmtpMailer('localhost', 25);
        $mail = (new Mail())->withTo('to@example.com')->withSubject('Test');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('No sender specified');

        $mailer->send($mail);
    }

    public function testConnectionFailureThrowsException(): void
    {
        // Use invalid host and port, expect connection failure
        $mailer = new SmtpMailer('127.0.0.1', 19999, timeout: 1);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withBody('Body');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('SMTP connection failed');

        $mailer->send($mail);
    }

    public function testImplementsMailerInterface(): void
    {
        $mailer = new SmtpMailer('localhost');
        self::assertInstanceOf(\MiGears\Mail\MailerInterface::class, $mailer);
    }
}
