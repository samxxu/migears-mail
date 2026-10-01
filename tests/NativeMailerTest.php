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

    public function testBuildHeadersStripsCrlfFromCustomHeaderValue(): void
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
            ->withHeaders(['X-Custom' => "safe\r\nBcc: evil@example.com"]);

        $mailer->send($mail);

        self::assertStringNotContainsString("\r\nBcc: ", $mailer->capturedHeaders);
        self::assertStringContainsString('X-Custom: safeBcc: evil@example.com', $mailer->capturedHeaders);
    }

    public function testBuildHeadersRejectsColonInCustomHeaderName(): void
    {
        $mailer = new class extends NativeMailer {
            public function send(Mail $mail): void
            {
                $this->buildHeaders($mail);
            }
        };

        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withHeaders(['Bcc: evil@example.com' => 'x']);

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('Invalid custom header name');

        $mailer->send($mail);
    }

    public function testBuildHeadersStripsCrlfFromCharset(): void
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
            ->withCharset("utf-8\r\nBcc: evil@example.com");

        $mailer->send($mail);

        self::assertStringNotContainsString("\r\nBcc: ", $mailer->capturedHeaders);
    }

    public function testBuildHeadersStripsCrlfFromFromName(): void
    {
        $mailer = new class extends NativeMailer {
            public string $capturedHeaders = '';

            public function send(Mail $mail): void
            {
                $this->capturedHeaders = $this->buildHeaders($mail);
            }
        };

        $mail = (new Mail())
            ->withFrom('from@example.com', "Sender\r\nBcc: evil@example.com")
            ->withTo('to@example.com')
            ->withSubject('Test');

        $mailer->send($mail);

        self::assertStringNotContainsString("\r\nBcc: ", $mailer->capturedHeaders);
        self::assertSame(1, substr_count($mailer->capturedHeaders, 'From: '));
    }

    public function testBuildHeadersWithAttachmentsSetsMultipartMixed(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'mailatt');
        file_put_contents($file, 'hello');
        try {
            $mailer = new class extends NativeMailer {
                public string $capturedHeaders = '';

                public function send(Mail $mail): void
                {
                    $boundary = $mail->hasAttachments() ? 'test-boundary' : null;
                    $this->capturedHeaders = $this->buildHeaders($mail, $boundary);
                }
            };

            $mail = (new Mail())
                ->withFrom('from@example.com')
                ->withTo('to@example.com')
                ->withSubject('Test')
                ->withBody('Body')
                ->withAttachment($file, 'doc.txt', 'text/plain');

            $mailer->send($mail);

            self::assertStringContainsString('Content-Type: multipart/mixed; boundary="test-boundary"', $mailer->capturedHeaders);
            self::assertStringNotContainsString('Content-Transfer-Encoding: base64', $mailer->capturedHeaders);
        } finally {
            @unlink($file);
        }
    }

    public function testSubjectIsStrippedOfCrlfBeforeMailCall(): void
    {
        $mailer = new CapturingNativeMailer();

        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject("Hello\r\nBcc: evil@example.com")
            ->withBody('Body');

        $mailer->send($mail);

        self::assertStringNotContainsString("\r\nBcc: ", $mailer->subject);
        self::assertStringNotContainsString("\nBcc: ", $mailer->subject);
    }

    public function testNonAsciiSubjectIsRfc2047Encoded(): void
    {
        $mailer = new CapturingNativeMailer();

        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('会议通知')
            ->withBody('Body');

        $mailer->send($mail);

        self::assertSame('=?utf-8?B?' . base64_encode('会议通知') . '?=', $mailer->subject);
    }

    public function testAsciiSubjectIsNotEncoded(): void
    {
        $mailer = new CapturingNativeMailer();

        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Plain ASCII subject')
            ->withBody('Body');

        $mailer->send($mail);

        self::assertSame('Plain ASCII subject', $mailer->subject);
    }

    public function testCustomHeadersOverrideBuiltInOnes(): void
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
            ->withBody('Body')
            ->withHeaders(['X-Mailer' => 'CustomMailer', 'Content-Type' => 'text/x-custom']);

        $mailer->send($mail);

        self::assertStringContainsString('X-Mailer: CustomMailer', $mailer->capturedHeaders);
        self::assertSame(1, substr_count($mailer->capturedHeaders, 'X-Mailer:'));
        self::assertStringContainsString('Content-Type: text/x-custom', $mailer->capturedHeaders);
        self::assertStringNotContainsString('Content-Type: text/plain', $mailer->capturedHeaders);
    }

    public function testCcOnlyMessageIsRejectedWithNoRecipient(): void
    {
        $mailer = new CapturingNativeMailer();
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withCc('cc@example.com')
            ->withSubject('Test')
            ->withBody('Body');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('No recipient specified');

        $mailer->send($mail);
    }

    public function testMissingAttachmentThrowsException(): void
    {
        $mailer = new CapturingNativeMailer();

        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withBody('Body')
            ->withAttachment('/no/such/file.txt');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('Attachment not found');

        $mailer->send($mail);
    }

    public function testAttachmentIsIncludedInDeliveredBody(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'mailatt') . '.txt';
        file_put_contents($file, 'attachment-content');
        try {
            $mailer = new CapturingNativeMailer();

            $mail = (new Mail())
                ->withFrom('from@example.com')
                ->withTo('to@example.com')
                ->withSubject('Test')
                ->withBody('Body')
                ->withAttachment($file, 'doc.txt', 'text/plain');

            $mailer->send($mail);

            self::assertStringContainsString('Content-Type: multipart/mixed', $mailer->headers);
            self::assertStringContainsString('Content-Disposition: attachment; filename="doc.txt"', $mailer->body);
            self::assertStringContainsString(base64_encode('attachment-content'), $mailer->body);
        } finally {
            @unlink($file);
        }
    }

    public function testNonAsciiDisplayNameAndAttachmentFilenameAreEncoded(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'mailatt') . '.pdf';
        file_put_contents($file, 'x');
        try {
            $mailer = new CapturingNativeMailer();

            $mail = (new Mail())
                ->withFrom('from@example.com', '张三')
                ->withTo('to@example.com')
                ->withSubject('Test')
                ->withBody('Body')
                ->withAttachment($file, '报告.pdf', 'application/pdf');

            $mailer->send($mail);

            self::assertStringContainsString(
                'From: =?utf-8?B?' . base64_encode('张三') . '?= <from@example.com>',
                $mailer->headers
            );
            self::assertStringContainsString("name*=utf-8''" . rawurlencode('报告.pdf'), $mailer->body);
            self::assertStringContainsString("filename*=utf-8''" . rawurlencode('报告.pdf'), $mailer->body);
        } finally {
            @unlink($file);
        }
    }

    public function testAttachmentNameAndTypeWithQuotesAreEscaped(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'mailatt') . '.txt';
        file_put_contents($file, 'x');
        try {
            $mailer = new CapturingNativeMailer();

            $mail = (new Mail())
                ->withFrom('from@example.com')
                ->withTo('to@example.com')
                ->withSubject('Test')
                ->withBody('Body')
                ->withAttachment($file, 'a"b.txt', 'text/plain; x="evil');

            $mailer->send($mail);

            self::assertStringContainsString('name="a\"b.txt"', $mailer->body);
            self::assertStringContainsString('filename="a\"b.txt"', $mailer->body);
            self::assertStringNotContainsString('x="evil', $mailer->body);
        } finally {
            @unlink($file);
        }
    }
}
