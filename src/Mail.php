<?php

declare(strict_types=1);

namespace MiGears\Mail;

use MiGears\Mail\Exception\MailException;

/**
 * Immutable mail message value object. Uses with* chained calls, each returning a new instance.
 *
 * @phpstan-type Attachment array{path: string, name: ?string, type: ?string}
 */
class Mail
{
    public const VERSION = '2.0.0';

    /**
     * @param list<string> $to
     * @param list<string> $cc
     * @param list<string> $bcc
     * @param array<string, string> $headers
     * @param list<Attachment> $attachments
     */
    public function __construct(
        public readonly string $from = '',
        public readonly string $fromName = '',
        public readonly array $to = [],
        public readonly array $cc = [],
        public readonly array $bcc = [],
        public readonly string $replyTo = '',
        public readonly string $subject = '',
        public readonly string $body = '',
        public readonly bool $isHtml = false,
        public readonly string $charset = 'utf-8',
        public readonly array $headers = [],
        public readonly array $attachments = [],
    ) {
    }

    public function withFrom(string $email, string $name = ''): self
    {
        self::validateEmail($email, 'from');
        return $this->copy(from: $email, fromName: $name);
    }

    public function withTo(string ...$emails): self
    {
        foreach ($emails as $email) {
            self::validateEmail($email, 'to');
        }
        return $this->copy(to: $emails);
    }

    public function withCc(string ...$emails): self
    {
        foreach ($emails as $email) {
            self::validateEmail($email, 'cc');
        }
        return $this->copy(cc: $emails);
    }

    public function withBcc(string ...$emails): self
    {
        foreach ($emails as $email) {
            self::validateEmail($email, 'bcc');
        }
        return $this->copy(bcc: $emails);
    }

    public function withReplyTo(string $email): self
    {
        self::validateEmail($email, 'reply-to');
        return $this->copy(replyTo: $email);
    }

    private static function validateEmail(string $email, string $field): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw MailException::from("Invalid $field address: $email");
        }
    }

    public function withSubject(string $subject): self
    {
        return $this->copy(subject: $subject);
    }

    public function withBody(string $body, bool $isHtml = false): self
    {
        return $this->copy(body: $body, isHtml: $isHtml);
    }

    public function withCharset(string $charset): self
    {
        return $this->copy(charset: $charset);
    }

    /** @param array<string, string> $headers */
    public function withHeaders(array $headers): self
    {
        return $this->copy(headers: $headers);
    }

    public function withAttachment(string $path, ?string $name = null, ?string $type = null): self
    {
        $attachments = $this->attachments;
        $attachments[] = ['path' => $path, 'name' => $name, 'type' => $type];
        return $this->copy(attachments: $attachments);
    }

    public function hasAttachments(): bool
    {
        return $this->attachments !== [];
    }

    public function getContentType(): string
    {
        return $this->isHtml ? 'text/html' : 'text/plain';
    }

    public function getFormattedFrom(): string
    {
        $name = str_replace(["\r", "\n"], '', $this->fromName);
        if ($name === '') {
            return $this->from;
        }
        $name = str_replace(['\\', '"'], ['\\\\', '\\"'], $name);
        return sprintf('"%s" <%s>', $name, $this->from);
    }

    private function copy(
        ?string $from = null,
        ?string $fromName = null,
        ?array $to = null,
        ?array $cc = null,
        ?array $bcc = null,
        ?string $replyTo = null,
        ?string $subject = null,
        ?string $body = null,
        ?bool $isHtml = null,
        ?string $charset = null,
        ?array $headers = null,
        ?array $attachments = null,
    ): self {
        return new self(
            from: $from ?? $this->from,
            fromName: $fromName ?? $this->fromName,
            to: $to ?? $this->to,
            cc: $cc ?? $this->cc,
            bcc: $bcc ?? $this->bcc,
            replyTo: $replyTo ?? $this->replyTo,
            subject: $subject ?? $this->subject,
            body: $body ?? $this->body,
            isHtml: $isHtml ?? $this->isHtml,
            charset: $charset ?? $this->charset,
            headers: $headers ?? $this->headers,
            attachments: $attachments ?? $this->attachments,
        );
    }
}
