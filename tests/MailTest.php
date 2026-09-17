<?php

declare(strict_types=1);

namespace MiGears\Mail\Tests;

use PHPUnit\Framework\TestCase;
use MiGears\Mail\Mail;

final class MailTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $mail = new Mail();

        self::assertSame('', $mail->from);
        self::assertSame('', $mail->fromName);
        self::assertSame([], $mail->to);
        self::assertSame([], $mail->cc);
        self::assertSame([], $mail->bcc);
        self::assertSame('', $mail->replyTo);
        self::assertSame('', $mail->subject);
        self::assertSame('', $mail->body);
        self::assertFalse($mail->isHtml);
        self::assertSame('utf-8', $mail->charset);
        self::assertSame([], $mail->headers);
        self::assertSame([], $mail->attachments);
    }

    public function testWithFrom(): void
    {
        $mail = new Mail();
        $newMail = $mail->withFrom('sender@example.com', 'Sender Name');

        // Original instance unchanged
        self::assertSame('', $mail->from);
        self::assertSame('', $mail->fromName);

        // New instance has values
        self::assertSame('sender@example.com', $newMail->from);
        self::assertSame('Sender Name', $newMail->fromName);
    }

    public function testWithTo(): void
    {
        $mail = (new Mail())->withTo('a@example.com', 'b@example.com');

        self::assertSame(['a@example.com', 'b@example.com'], $mail->to);
    }

    public function testWithCcAndBcc(): void
    {
        $mail = (new Mail())
            ->withCc('cc@example.com')
            ->withBcc('bcc@example.com');

        self::assertSame(['cc@example.com'], $mail->cc);
        self::assertSame(['bcc@example.com'], $mail->bcc);
    }

    public function testWithReplyTo(): void
    {
        $mail = (new Mail())->withReplyTo('reply@example.com');

        self::assertSame('reply@example.com', $mail->replyTo);
    }

    public function testWithSubject(): void
    {
        $mail = (new Mail())->withSubject('Hello World');

        self::assertSame('Hello World', $mail->subject);
    }

    public function testWithBodyText(): void
    {
        $mail = (new Mail())->withBody('Plain text body');

        self::assertSame('Plain text body', $mail->body);
        self::assertFalse($mail->isHtml);
        self::assertSame('text/plain', $mail->getContentType());
    }

    public function testWithBodyHtml(): void
    {
        $mail = (new Mail())->withBody('<p>HTML body</p>', true);

        self::assertSame('<p>HTML body</p>', $mail->body);
        self::assertTrue($mail->isHtml);
        self::assertSame('text/html', $mail->getContentType());
    }

    public function testWithCharset(): void
    {
        $mail = (new Mail())->withCharset('gbk');

        self::assertSame('gbk', $mail->charset);
    }

    public function testWithHeaders(): void
    {
        $mail = (new Mail())->withHeaders(['X-Custom' => 'value']);

        self::assertSame(['X-Custom' => 'value'], $mail->headers);
    }

    public function testWithAttachment(): void
    {
        $mail = (new Mail())
            ->withAttachment('/path/to/file.pdf', 'doc.pdf', 'application/pdf');

        self::assertTrue($mail->hasAttachments());
        self::assertCount(1, $mail->attachments);
        self::assertSame('/path/to/file.pdf', $mail->attachments[0]['path']);
        self::assertSame('doc.pdf', $mail->attachments[0]['name']);
        self::assertSame('application/pdf', $mail->attachments[0]['type']);
    }

    public function testMultipleAttachments(): void
    {
        $mail = (new Mail())
            ->withAttachment('/a.txt')
            ->withAttachment('/b.txt');

        self::assertCount(2, $mail->attachments);
    }

    public function testNoAttachments(): void
    {
        $mail = new Mail();
        self::assertFalse($mail->hasAttachments());
    }

    public function testImmutability(): void
    {
        $original = new Mail();
        $withSubject = $original->withSubject('Test');
        $withBody = $withSubject->withBody('Body');

        self::assertSame('', $original->subject);
        self::assertSame('', $original->body);
        self::assertSame('Test', $withSubject->subject);
        self::assertSame('', $withSubject->body);
        self::assertSame('Test', $withBody->subject);
        self::assertSame('Body', $withBody->body);
    }

    public function testFluentChain(): void
    {
        $mail = (new Mail())
            ->withFrom('from@example.com', 'From Name')
            ->withTo('to@example.com')
            ->withSubject('Test Subject')
            ->withBody('Test Body', true)
            ->withCc('cc@example.com')
            ->withBcc('bcc@example.com')
            ->withReplyTo('reply@example.com');

        self::assertSame('from@example.com', $mail->from);
        self::assertSame('From Name', $mail->fromName);
        self::assertSame(['to@example.com'], $mail->to);
        self::assertSame('Test Subject', $mail->subject);
        self::assertSame('Test Body', $mail->body);
        self::assertTrue($mail->isHtml);
        self::assertSame(['cc@example.com'], $mail->cc);
        self::assertSame(['bcc@example.com'], $mail->bcc);
        self::assertSame('reply@example.com', $mail->replyTo);
    }

    public function testGetFormattedFromWithName(): void
    {
        $mail = (new Mail())->withFrom('test@example.com', 'Test User');
        self::assertSame('"Test User" <test@example.com>', $mail->getFormattedFrom());
    }

    public function testGetFormattedFromWithoutName(): void
    {
        $mail = (new Mail())->withFrom('test@example.com');
        self::assertSame('test@example.com', $mail->getFormattedFrom());
    }

    public function testWithToReplacesNotAppends(): void
    {
        $mail = (new Mail())->withTo('first@example.com');
        $mail2 = $mail->withTo('second@example.com');

        self::assertSame(['first@example.com'], $mail->to);
        self::assertSame(['second@example.com'], $mail2->to);
    }

    public function testReadonlyProperties(): void
    {
        $mail = (new Mail())->withSubject('Test');

        $this->expectException(\Error::class);
        $mail->subject = 'Changed'; // @phpstan-ignore-line
    }
}
