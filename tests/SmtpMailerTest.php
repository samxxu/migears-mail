<?php

declare(strict_types=1);

namespace MiGears\Mail\Tests;

use PHPUnit\Framework\TestCase;
use MiGears\Mail\Exception\MailException;
use MiGears\Mail\Mail;
use MiGears\Mail\SmtpMailer;

final class SmtpMailerTest extends TestCase
{
    /** @return list<string> Standard happy-path responses for a single-recipient send. */
    private static function happyPathResponses(): array
    {
        return ['220 ready', '250 ok', '250 ok', '250 ok', '354 ok', '250 ok', '221 ok'];
    }

    public function testSendWithoutRecipientsThrowsException(): void
    {
        $mailer = new SmtpMailer('localhost', 25, transport: new FakeSmtpTransport());
        $mail = (new Mail())->withFrom('from@example.com')->withSubject('Test');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('No recipient specified');

        $mailer->send($mail);
    }

    public function testSendWithoutSenderThrowsException(): void
    {
        $mailer = new SmtpMailer('localhost', 25, transport: new FakeSmtpTransport());
        $mail = (new Mail())->withTo('to@example.com')->withSubject('Test');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('No sender specified');

        $mailer->send($mail);
    }

    public function testConnectionFailureThrowsException(): void
    {
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

    public function testSendBuildsPlainHtmlMessage(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = self::happyPathResponses();

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com', 'From Name')
            ->withTo('to@example.com')
            ->withReplyTo('reply@example.com')
            ->withSubject('Hello')
            ->withBody('<p>Hi</p>', isHtml: true);

        $mailer->send($mail);

        self::assertSame(1, $transport->connectCount);
        self::assertTrue($transport->closed);
        $payload = $transport->payload();

        self::assertStringContainsString('MAIL FROM:<from@example.com>', $payload);
        self::assertStringContainsString('RCPT TO:<to@example.com>', $payload);
        self::assertStringContainsString('EHLO ', $payload);
        self::assertStringContainsString('QUIT', $payload);
        self::assertStringContainsString('From: "From Name" <from@example.com>', $payload);
        self::assertStringContainsString('Reply-To: reply@example.com', $payload);
        self::assertStringContainsString('Subject: Hello', $payload);
        self::assertStringContainsString('MIME-Version: 1.0', $payload);
        self::assertStringContainsString('Content-Type: text/html; charset=utf-8', $payload);
    }

    public function testSendSendsRcptsForToCcAndBcc(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = ['220 ready', '250 ok', '250 ok', '250 ok', '250 ok', '250 ok', '354 ok', '250 ok', '221 ok'];

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('a@example.com')
            ->withCc('c@example.com')
            ->withBcc('b@example.com')
            ->withSubject('Groups');

        $mailer->send($mail);

        $payload = $transport->payload();
        self::assertStringContainsString('RCPT TO:<a@example.com>', $payload);
        self::assertStringContainsString('RCPT TO:<c@example.com>', $payload);
        self::assertStringContainsString('RCPT TO:<b@example.com>', $payload);
        self::assertStringContainsString('Cc: c@example.com', $payload);
        // BCC recipients belong to the SMTP envelope only, never in the message headers.
        self::assertStringNotContainsString('Bcc:', $payload);
    }

    public function testSendWithAttachmentBuildsMultipart(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'mail') . '.txt';
        file_put_contents($file, 'attachment-content');

        try {
            $transport = new FakeSmtpTransport();
            $transport->responses = self::happyPathResponses();

            $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
            $mail = (new Mail())
                ->withFrom('from@example.com')
                ->withTo('to@example.com')
                ->withSubject('Attach')
                ->withAttachment($file, 'doc.txt', 'text/plain');

            $mailer->send($mail);

            $payload = $transport->payload();
            self::assertStringContainsString('multipart/mixed; boundary="', $payload);
            self::assertStringContainsString(
                'Content-Disposition: attachment; filename="doc.txt"',
                $payload
            );
            self::assertStringContainsString(base64_encode('attachment-content'), $payload);
        } finally {
            @unlink($file);
        }
    }

    public function testSendWithMissingAttachmentThrows(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = self::happyPathResponses();

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Attach')
            ->withAttachment('/nonexistent/file.pdf', 'x.pdf', 'application/pdf');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('Attachment not found');

        $mailer->send($mail);
    }

    public function testSendEncodesNonAsciiSubject(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = self::happyPathResponses();

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('测试邮件')
            ->withBody('Body');

        $mailer->send($mail);

        self::assertStringContainsString('Subject: =?utf-8?B?' . base64_encode('测试邮件') . '?=', $transport->payload());
    }

    public function testStartTlsIsNegotiatedWhenAdvertised(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = [
            '220 ready',                    // server greeting
            '250-STARTTLS', '250 ok',        // EHLO advertises STARTTLS
            '220 ready',                     // STARTTLS response
            '250 ok',                        // second EHLO
            '250 ok', '250 ok', '354 ok', '250 ok', '221 ok',
        ];

        $mailer = new SmtpMailer('smtp.example.com', 25, encryption: 'tls', transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('TLS');

        $mailer->send($mail);

        $payload = $transport->payload();
        self::assertTrue($transport->tlsEnabled);
        self::assertStringContainsString('STARTTLS', $payload);
        self::assertSame(2, substr_count($payload, 'EHLO '));
    }

    public function testTlsRequestedButUnsupportedThrows(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = ['220 ready', '250 ok']; // no 250-STARTTLS advertised

        $mailer = new SmtpMailer('smtp.example.com', 25, encryption: 'tls', transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('TLS');

        try {
            $mailer->send($mail);
            self::fail('Expected MailException to be thrown');
        } catch (MailException $e) {
            self::assertStringContainsString('does not support STARTTLS', $e->getMessage());
        }

        self::assertFalse($transport->tlsEnabled);
        self::assertTrue($transport->closed);
    }

    public function testSendWithAuthPerformsAuthLogin(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = [
            '220 ready',   // server greeting
            '250 ok',      // EHLO
            '334 ',        // AUTH LOGIN
            '334 ',        // username
            '235 ok',      // password accepted
            '250 ok', '250 ok', '354 ok', '250 ok', '221 ok',
        ];

        $mailer = new SmtpMailer('smtp.example.com', 25, username: 'user', password: 'pass', transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Auth');

        $mailer->send($mail);

        $payload = $transport->payload();
        self::assertStringContainsString('AUTH LOGIN', $payload);
        self::assertStringContainsString(base64_encode('user'), $payload);
        self::assertStringContainsString(base64_encode('pass'), $payload);
    }

    public function testSendWithoutAuthSkipsAuthLogin(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = self::happyPathResponses();

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('No auth');

        $mailer->send($mail);

        self::assertStringNotContainsString('AUTH ', $transport->payload());
    }

    public function testSendWhenServerReturnsErrorThrows(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = ['220 ready', '250 ok', '250 ok', '550 mailbox unavailable'];

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Error');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('SMTP error: expected 250, got 550');

        $mailer->send($mail);
    }

    public function testSendWhenConnectionClosesUnexpectedlyThrows(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = []; // immediate EOF

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Closed');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('SMTP connection closed unexpectedly');

        $mailer->send($mail);
    }

    public function testEncryptionModeIsCaseInsensitive(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = [
            '220 ready', '250-STARTTLS', '250 ok', '220 ready', '250 ok',
            '250 ok', '250 ok', '354 ok', '250 ok', '221 ok',
        ];

        $mailer = new SmtpMailer('smtp.example.com', 25, encryption: 'TLS', transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('TLS');

        $mailer->send($mail);

        self::assertTrue($transport->tlsEnabled);
        self::assertStringContainsString('STARTTLS', $transport->payload());
    }

    public function testInvalidEncryptionModeThrows(): void
    {
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('Unsupported SMTP encryption mode');

        new SmtpMailer('smtp.example.com', 25, encryption: 'sssl');
    }

    public function testFromNameWithCrlfIsStripped(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = self::happyPathResponses();

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com', "From Name\r\nBcc: evil@example.com")
            ->withTo('to@example.com')
            ->withSubject('Test');

        $mailer->send($mail);

        $payload = $transport->payload();
        self::assertStringNotContainsString("\r\nBcc: ", $payload);
        self::assertSame(1, substr_count($payload, 'From: '));
    }

    public function testSubjectWithCrlfIsStripped(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = self::happyPathResponses();

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject("Hi\r\nBcc: evil@example.com");

        $mailer->send($mail);

        self::assertStringNotContainsString("\r\nBcc: ", $transport->payload());
    }

    public function testCustomHeaderValueWithCrlfIsStripped(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = self::happyPathResponses();

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withHeaders(['X-Custom' => "safe\r\nBcc: evil@example.com"]);

        $mailer->send($mail);

        self::assertStringNotContainsString("\r\nBcc: ", $transport->payload());
    }

    public function testCustomHeaderNameWithColonThrows(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = self::happyPathResponses();

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withHeaders(['Bcc: evil@example.com' => 'x']);

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('Invalid custom header name');

        $mailer->send($mail);
    }

    public function testCharsetWithCrlfIsStripped(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = self::happyPathResponses();

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withCharset("utf-8\r\nBcc: evil@example.com");

        $mailer->send($mail);

        self::assertStringNotContainsString("\r\nBcc: ", $transport->payload());
    }

    public function testAttachmentNameWithCrlfIsStripped(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'mail') . '.txt';
        file_put_contents($file, 'x');

        try {
            $transport = new FakeSmtpTransport();
            $transport->responses = self::happyPathResponses();

            $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
            $mail = (new Mail())
                ->withFrom('from@example.com')
                ->withTo('to@example.com')
                ->withSubject('Test')
                ->withAttachment($file, "doc.txt\r\nBcc: evil@example.com", 'text/plain');

            $mailer->send($mail);

            self::assertStringNotContainsString("\r\nBcc: ", $transport->payload());
        } finally {
            @unlink($file);
        }
    }

    public function testExpectHandlesBareStatusCodeLine(): void
    {
        // A bare "250\r\n" (no space, no text) must be treated as final,
        // not as a continuation — otherwise the next response gets eaten
        // and the session desynchronises.
        $transport = new FakeSmtpTransport();
        $transport->responses = [
            "220 ready\r\n",
            "250\r\n",          // EHLO response: bare status, no text
            "250 ok\r\n",       // MAIL FROM
            "250 ok\r\n",       // RCPT TO
            "354 ok\r\n",
            "250 ok\r\n",
            "221 ok\r\n",
        ];

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withBody('Body');

        $mailer->send($mail);

        // If the session stayed in sync, QUIT was the last command sent
        $cmds = $transport->commands();
        self::assertSame('QUIT', end($cmds));
    }

    public function testExpectTruncatedMultilineResponseThrows(): void
    {
        // When a multi-line EHLO response is cut off mid-stream (no final
        // line with trailing space), we must throw instead of silently
        // treating the partial response as success.
        $transport = new FakeSmtpTransport();
        $transport->responses = [
            "220 ready\r\n",
            "250-smtp.example.com\r\n",   // EHLO line 1 (continuation)
            "250-AUTH LOGIN\r\n",         // EHLO line 2 (continuation)
            // ...connection drops before the final "250 STARTTLS" line
            false,
        ];

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withBody('Body');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('closed mid-response');

        $mailer->send($mail);
    }

    public function testStarttlsWorksWhenLastEhloLine(): void
    {
        // STARTTLS advertised on the final EHLO line (250 STARTTLS, no dash)
        $transport = new FakeSmtpTransport();
        $transport->responses = [
            "220 ready\r\n",
            "250-smtp.example.com\r\n",
            "250-AUTH LOGIN\r\n",
            "250 STARTTLS\r\n",
            "220 Ready to start TLS\r\n",
            "250-smtp.example.com\r\n",
            "250 AUTH LOGIN\r\n",
            "250 ok\r\n",  // MAIL FROM
            "250 ok\r\n",  // RCPT TO
            "354 ok\r\n",
            "250 ok\r\n",
            "221 ok\r\n",
        ];

        $mailer = new SmtpMailer('smtp.example.com', 587, encryption: 'tls', transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withBody('Body');

        $mailer->send($mail);

        $cmds = $transport->commands();
        self::assertContains('STARTTLS', $cmds);
    }

    public function testStarttlsIsCaseInsensitive(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = [
            "220 ready\r\n",
            "250-smtp.example.com\r\n",
            "250 starttls\r\n",        // lower case, last line form
            "220 Ready to start TLS\r\n",
            "250-smtp.example.com\r\n",
            "250 AUTH LOGIN\r\n",
            "250 ok\r\n",
            "250 ok\r\n",
            "354 ok\r\n",
            "250 ok\r\n",
            "221 ok\r\n",
        ];

        $mailer = new SmtpMailer('smtp.example.com', 587, encryption: 'tls', transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withBody('Body');

        $mailer->send($mail);

        $cmds = $transport->commands();
        self::assertContains('STARTTLS', $cmds);
    }

    public function testCcOnlyMessageOmitsToHeader(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = self::happyPathResponses();

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withCc('cc@example.com')
            ->withSubject('Test')
            ->withBody('Body');

        $mailer->send($mail);

        self::assertStringNotContainsString("\r\nTo: \r\n", $transport->payload());
        self::assertStringNotContainsString("\nTo: \r\n", $transport->payload());
        // But Cc header is present
        self::assertStringContainsString('Cc: cc@example.com', $transport->payload());
    }

    public function testOnlyUsernameThrows(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = ['220 ready', '250 ok', '250 ok'];

        $mailer = new SmtpMailer('smtp.example.com', 25, username: 'user', transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withBody('Body');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('username and password must both be provided');

        $mailer->send($mail);
    }

    public function testOnlyPasswordThrows(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = ['220 ready', '250 ok', '250 ok'];

        $mailer = new SmtpMailer('smtp.example.com', 25, password: 'pass', transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withBody('Body');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('username and password must both be provided');

        $mailer->send($mail);
    }

    public function testCustomHeaderOverridesContentTypeAndCte(): void
    {
        $transport = new FakeSmtpTransport();
        $transport->responses = self::happyPathResponses();

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withBody('Body')
            ->withHeaders([
                'X-Mailer' => 'CUSTOM',
                'Content-Type' => 'text/x-custom',
                'Content-Transfer-Encoding' => 'quoted-printable',
            ]);

        $mailer->send($mail);

        $payload = $transport->payload();
        self::assertStringContainsString('Content-Type: text/x-custom', $payload);
        self::assertStringContainsString('Content-Transfer-Encoding: quoted-printable', $payload);
        self::assertStringNotContainsString('Content-Type: text/plain', $payload);
        self::assertSame(1, substr_count($payload, 'Content-Type:'));
    }

    public function testContinuationLineWithWrongCodeThrows(): void
    {
        // A multi-line response whose continuation line carries a different
        // status code must not be accepted as success.
        $transport = new FakeSmtpTransport();
        $transport->responses = [
            "220 ready\r\n",
            "250-smtp.example.com\r\n",
            "550 bad\r\n",
        ];

        $mailer = new SmtpMailer('smtp.example.com', 25, transport: $transport);
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withBody('Body');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('expected 250, got 550');

        $mailer->send($mail);
    }

    public function testPlaintextAuthIsSentWhenEncryptionIsEmpty(): void
    {
        // Documents the (unencrypted) behaviour when the caller explicitly
        // opts out of encryption while still supplying credentials.
        $transport = new FakeSmtpTransport();
        $transport->responses = [
            '220 ready',
            '250 ok',
            '334 VXNlcm5hbWU6',
            '334 UGFzc3dvcmQ6',
            '235 ok',
            '250 ok',
            '250 ok',
            '354 ok',
            '250 ok',
            '221 ok',
        ];

        $mailer = new SmtpMailer(
            'smtp.example.com',
            25,
            username: 'user',
            password: 'pass',
            transport: $transport
        );
        $mail = (new Mail())
            ->withFrom('from@example.com')
            ->withTo('to@example.com')
            ->withSubject('Test')
            ->withBody('Body');

        $mailer->send($mail);

        $cmds = $transport->commands();
        self::assertContains('AUTH LOGIN', $cmds);
        self::assertContains(base64_encode('user'), $cmds);
        self::assertContains(base64_encode('pass'), $cmds);
    }
}