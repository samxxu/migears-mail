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
        $subject = $this->stripCrlf($mail->subject);
        $boundary = $mail->hasAttachments() ? '----=_Part_' . md5(uniqid()) : null;
        $body = $this->buildBody($mail, $boundary);
        $headers = $this->buildHeaders($mail, $boundary);

        $result = @mail($to, $subject, $body, $headers);

        if ($result === false) {
            throw MailException::from('Failed to send mail via native mail() function');
        }
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
            $body .= $this->buildAttachmentPart($att, $boundary);
        }
        $body .= '--' . $boundary . '--';

        return $body;
    }

    /** @param array{path: string, name: ?string, type: ?string} $att */
    private function buildAttachmentPart(array $att, string $boundary): string
    {
        $filePath = $att['path'];
        $fileName = $this->stripCrlf($att['name'] ?? basename($filePath));
        $mimeType = $this->stripCrlf($att['type'] ?? 'application/octet-stream');

        if (!is_file($filePath)) {
            throw MailException::from("Attachment not found: $filePath");
        }
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw MailException::from("Cannot read attachment: $filePath");
        }

        return '--' . $boundary . "\r\n"
            . 'Content-Type: ' . $mimeType . '; name="' . $fileName . '"' . "\r\n"
            . "Content-Transfer-Encoding: base64\r\n"
            . 'Content-Disposition: attachment; filename="' . $fileName . '"' . "\r\n\r\n"
            . chunk_split(base64_encode($content)) . "\r\n";
    }

    protected function buildHeaders(Mail $mail, ?string $boundary = null): string
    {
        $headers = [];

        if ($mail->from !== '') {
            $headers['From'] = $mail->getFormattedFrom();
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
