<?php

declare(strict_types=1);

namespace MiGears\Mail\Tests;

use PHPUnit\Framework\TestCase;
use MiGears\Mail\Exception\MailException;
use MiGears\Mail\Mail;
use MiGears\Mail\NativeMailer;

final class NativeMailerTest extends TestCase
{
    public function testSendWithoutRecipientsThrowsException(): void
    {
        $mailer = new NativeMailer();
        $mail = (new Mail())->withFrom('from@example.com')->withSubject('Test');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('No recipient specified');

        $mailer->send($mail);
    }

    public function testSendWithoutSenderThrowsException(): void
    {
        $mailer = new NativeMailer();
        $mail = (new Mail())->withTo('to@example.com')->withSubject('Test');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('No sender specified');

        $mailer->send($mail);
    }

    public function testImplementsMailerInterface(): void
    {
        $mailer = new NativeMailer();
        self::assertInstanceOf(\MiGears\Mail\MailerInterface::class, $mailer);
    }

    public function testBuildHeadersWithFrom(): void
    {
        $mailer = new class extends NativeMailer {
            public string $capturedHeaders = '';

            public function send(Mail $mail): void
            {
                $this->capturedHeaders = $this->buildHeaders($mail);
            }
        };

        $mail = (new Mail())
            ->withFrom('sender@example.com', 'Sender')
            ->withTo('to@example.com')
            ->withCc('cc@example.com')
            ->withBcc('bcc@example.com')
            ->withReplyTo('reply@example.com')
            ->withSubject('Test')
            ->withBody('Body');

        $mailer->send($mail);

        self::assertStringContainsString('From: "Sender" <sender@example.com>', $mailer->capturedHeaders);
        self::assertStringContainsString('Reply-To: reply@example.com', $mailer->capturedHeaders);
        self::assertStringContainsString('Cc: cc@example.com', $mailer->capturedHeaders);
        self::assertStringContainsString('Bcc: bcc@example.com', $mailer->capturedHeaders);
        self::assertStringContainsString('MIME-Version: 1.0', $mailer->capturedHeaders);
        self::assertStringContainsString('Content-Type: text/plain; charset=utf-8', $mailer->capturedHeaders);
        self::assertStringContainsString('X-Mailer: miGears-Mail', $mailer->capturedHeaders);
    }

    public function testBuildHeadersHtmlContentType(): void
    {
        $mailer = new class extends NativeMailer {
            public string $capturedHeaders = '';

            public function send(Mail $mail): void
            {
                $this->capturedHeaders = $this->buildHeaders($mail);
            }
        };

        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withBody('<p>HTML</p>', true);

        $mailer->send($mail);

        self::assertStringContainsString('Content-Type: text/html; charset=utf-8', $mailer->capturedHeaders);
    }

    public function testBuildHeadersWithoutFrom(): void
    {
        $mailer = new class extends NativeMailer {
            public string $capturedHeaders = '';

            public function send(Mail $mail): void
            {
                $this->capturedHeaders = $this->buildHeaders($mail);
            }
        };

        $mail = (new Mail())
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withBody('Body');

        $mailer->send($mail);

        self::assertStringNotContainsString('From:', $mailer->capturedHeaders);
    }
}
