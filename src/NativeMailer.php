<?php

declare(strict_types=1);

namespace MiGears\Mail;

use MiGears\Mail\Exception\MailException;

/**
 * Sends email using PHP's native mail() function.
 */
class NativeMailer implements MailerInterface
{
    public function send(Mail $mail): void
    {
        if ($mail->to === []) {
            throw MailException::from('No recipient specified');
        }
        if ($mail->from === '') {
            throw MailException::from('No sender specified');
        }

        $to = implode(', ', $mail->to);
        $subject = $this->encodeSubject($mail->subject, $mail->charset);
        $boundary = $mail->hasAttachments() ? '----=_Part_' . md5(uniqid()) : null;
        $body = $this->buildBody($mail, $boundary);
        $headers = $this->buildHeaders($mail, $boundary);

        $result = $this->deliver($to, $subject, $body, $headers);

        if ($result === false) {
            throw MailException::from('Failed to send mail via native mail() function');
        }
    }

    /**
     * Thin seam over mail() so tests can assert the exact arguments that
     * would be handed to the MTA. Override in a subclass to capture them.
     */
    protected function deliver(string $to, string $subject, string $body, string $headers): bool
    {
        return @mail($to, $subject, $body, $headers);
    }

    private function buildBody(Mail $mail, ?string $boundary): string
    {
        if ($boundary === null) {
            return chunk_split(base64_encode($mail->body));
        }

        $body = '--' . $boundary . "\r\n";
        $body .= 'Content-Type: ' . $mail->getContentType() . '; charset=' . $this->stripCrlf($mail->charset) . "\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($mail->body)) . "\r\n";
        foreach ($mail->attachments as $att) {
            $body .= $this->buildAttachmentPart($att, $boundary, $mail->charset);
        }
        $body .= '--' . $boundary . '--';

        return $body;
    }

    /** @param array{path: string, name: ?string, type: ?string} $att */
    private function buildAttachmentPart(array $att, string $boundary, string $charset): string
    {
        $filePath = $att['path'];
        $fileName = $this->stripCrlf($att['name'] ?? basename($filePath));
        // A media type is a `type/subtype` token pair; `"` and `\` are not
        // part of it and would break out of the `Content-Type:` value.
        $mimeType = str_replace(['"', '\\'], '', $this->stripCrlf($att['type'] ?? 'application/octet-stream'));

        if (!is_file($filePath)) {
            throw MailException::from("Attachment not found: $filePath");
        }
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw MailException::from("Cannot read attachment: $filePath");
        }

        return '--' . $boundary . "\r\n"
            . 'Content-Type: ' . $mimeType . '; ' . $this->mimeParameter('name', $fileName, $charset) . "\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . 'Content-Disposition: attachment; ' . $this->mimeParameter('filename', $fileName, $charset) . "\r\n\r\n"
            . chunk_split(base64_encode($content)) . "\r\n";
    }

    protected function buildHeaders(Mail $mail, ?string $boundary = null): string
    {
        $headers = [];

        if ($mail->from !== '') {
            $headers['From'] = $this->formatFrom($mail);
        }

        if ($mail->replyTo !== '') {
            $headers['Reply-To'] = $mail->replyTo;
        }

        if ($mail->cc !== []) {
            $headers['Cc'] = implode(', ', $mail->cc);
        }

        if ($mail->bcc !== []) {
            $headers['Bcc'] = implode(', ', $mail->bcc);
        }

        $headers['MIME-Version'] = '1.0';
        if ($boundary !== null) {
            $headers['Content-Type'] = 'multipart/mixed; boundary="' . $boundary . '"';
        } else {
            $headers['Content-Type'] = sprintf('%s; charset=%s', $mail->getContentType(), $this->stripCrlf($mail->charset));
            $headers['Content-Transfer-Encoding'] = 'base64';
        }
        $headers['X-Mailer'] = 'miGears-Mail';

        // Custom headers are applied last so they can override built-in ones
        foreach ($mail->headers as $name => $value) {
            $headers[$name] = $value;
        }

        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = sprintf('%s: %s', $this->sanitizeHeaderName($name), $this->stripCrlf($value));
        }

        return implode("\r\n", $lines);
    }

    private function encodeSubject(string $subject, string $charset): string
    {
        return preg_match('/[^\x20-\x7E]/', $subject)
            ? sprintf('=?%s?B?%s?=', $this->stripCrlf($charset), base64_encode($subject))
            : $this->stripCrlf($subject);
    }

    /**
     * Formats the `From` value. A non-ASCII display name is RFC 2047
     * base64-encoded as a phrase, the same way the subject is encoded, so a
     * conforming client decodes it instead of showing raw bytes.
     */
    private function formatFrom(Mail $mail): string
    {
        if ($mail->fromName === '' || preg_match('/[^\x20-\x7E]/', $mail->fromName) !== 1) {
            return $mail->getFormattedFrom();
        }

        return sprintf('=?%s?B?%s?= <%s>', $this->stripCrlf($mail->charset), base64_encode($mail->fromName), $mail->from);
    }

    /**
     * A `name="value"` MIME parameter. The value is escaped as a quoted-string
     * and, when it is not plain ASCII, an RFC 2231 `name*=` companion is added
     * so conforming clients decode the real bytes.
     */
    private function mimeParameter(string $name, string $value, string $charset): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value) !== 1) {
            return $name . '="' . $this->escapeQuoted($value) . '"';
        }

        $fallback = (string) preg_replace('/[^\x20-\x7E]/', '_', $value);

        return $name . '="' . $this->escapeQuoted($fallback) . '"; '
            . $name . '*=' . $this->stripCrlf($charset) . "''" . rawurlencode($value);
    }

    private function escapeQuoted(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }

    private function stripCrlf(string $value): string
    {
        return str_replace(["\r", "\n"], '', $value);
    }

    private function sanitizeHeaderName(string $name): string
    {
        $name = $this->stripCrlf($name);
        if ($name === '' || str_contains($name, ':')) {
            throw MailException::from("Invalid custom header name: $name");
        }
        return $name;
    }
}
