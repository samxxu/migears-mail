<?php

declare(strict_types=1);

namespace MiGears\Mail\Tests;

use PHPUnit\Framework\TestCase;
use MiGears\Mail\Mail;

final class InMemoryMailerTest extends TestCase
{
    public function testSendStoresMail(): void
    {
        $mailer = new InMemoryMailer();
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withBody('Body');

        $mailer->send($mail);

        self::assertSame(1, $mailer->getSentCount());
        self::assertSame($mail, $mailer->getLastMail());
    }

    public function testSendMultipleMails(): void
    {
        $mailer = new InMemoryMailer();

        $mail1 = (new Mail())->withSubject('First');
        $mail2 = (new Mail())->withSubject('Second');

        $mailer->send($mail1);
        $mailer->send($mail2);

        self::assertSame(2, $mailer->getSentCount());
        self::assertSame($mail2, $mailer->getLastMail());

        $mails = $mailer->getSentMails();
        self::assertSame('First', $mails[0]->subject);
        self::assertSame('Second', $mails[1]->subject);
    }

    public function testClear(): void
    {
        $mailer = new InMemoryMailer();
        $mailer->send(new Mail());

        self::assertSame(1, $mailer->getSentCount());

        $mailer->clear();

        self::assertSame(0, $mailer->getSentCount());
        self::assertNull($mailer->getLastMail());
    }

    public function testGetLastMailWhenEmpty(): void
    {
        $mailer = new InMemoryMailer();
        self::assertNull($mailer->getLastMail());
    }

    public function testImplementsMailerInterface(): void
    {
        $mailer = new InMemoryMailer();
        self::assertInstanceOf(\MiGears\Mail\MailerInterface::class, $mailer);
    }
}
