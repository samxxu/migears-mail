<?php

declare(strict_types=1);

namespace MiGears\Mail;

use MiGears\Mail\Exception\MailException;
use MiGears\Mail\Transport\SmtpTransport;
use MiGears\Mail\Transport\SocketSmtpTransport;

/**
 * SMTP mail sender implementation, directly implementing the SMTP protocol.
 * Supports LOGIN authentication, TLS/SSL encryption, attachments, and HTML emails.
 * The transport can be injected for testing.
 */
class SmtpMailer implements MailerInterface
{
    private readonly string $encryption;
    private string $localhost;

    public function __construct(
        private readonly string $host,
        private readonly int $port = 25,
        private readonly string $username = '',
        private readonly string $password = '',
        string $encryption = '', // '', 'tls', 'ssl'
        private readonly int $timeout = 30,
        private readonly ?SmtpTransport $transport = null,
    ) {
        $this->encryption = strtolower($encryption);
        if (!in_array($this->encryption, ['', 'tls', 'ssl'], true)) {
            throw MailException::from("Unsupported SMTP encryption mode: $encryption");
        }
        $this->localhost = gethostname() ?: 'localhost';
    }

    public function send(Mail $mail): void
    {
        if ($mail->to === [] && $mail->cc === [] && $mail->bcc === []) {
            throw MailException::from('No recipient specified');
        }
        if ($mail->from === '') {
            throw MailException::from('No sender specified');
        }

        $transport = $this->transport ?? new SocketSmtpTransport();
        try {
            $transport->connect($this->host, $this->port, $this->encryption, $this->timeout);
            $this->expect($transport, '220');
            $this->ehlo($transport);
            $this->authenticate($transport);
            $this->cmd($transport, 'MAIL FROM:<' . $mail->from . '>', '250');
            foreach (array_merge($mail->to, $mail->cc, $mail->bcc) as $rcpt) {
                $this->cmd($transport, 'RCPT TO:<' . $rcpt . '>', '250');
            }
            $this->cmd($transport, 'DATA', '354');
            $transport->write(str_replace("\r\n.", "\r\n..", $this->buildMessage($mail)) . "\r\n");
            $this->cmd($transport, '.', '250');
            $this->cmd($transport, 'QUIT', '221');
        } finally {
            $transport->close();
        }
    }

    private function ehlo(SmtpTransport $transport): void
    {
        $response = $this->cmd($transport, 'EHLO ' . $this->localhost, '250');
        if ($this->encryption === 'tls') {
            $hasStarttls = stripos($response, '250-STARTTLS') !== false
                || preg_match('/^250 STARTTLS\s*$/mi', $response) === 1;
            if (!$hasStarttls) {
                throw MailException::from('Server does not support STARTTLS but TLS was requested');
            }
            $this->cmd($transport, 'STARTTLS', '220');
            $transport->enableTls();
            $this->cmd($transport, 'EHLO ' . $this->localhost, '250');
        }
    }

    private function authenticate(SmtpTransport $transport): void
    {
        if ($this->username === '' && $this->password === '') {
            return;
        }
        if ($this->username === '' || $this->password === '') {
            throw MailException::from('SMTP username and password must both be provided');
        }
        $this->cmd($transport, 'AUTH LOGIN', '334');
        $this->cmd($transport, base64_encode($this->username), '334');
        $this->cmd($transport, base64_encode($this->password), '235');
    }

    private function buildMessage(Mail $mail): string
    {
        $boundary = $mail->hasAttachments() ? '----=_Part_' . md5(uniqid()) : null;
        $headers = [
            'From' => $this->formatFrom($mail),
            'Subject' => $this->encodeSubject($mail->subject, $mail->charset),
            'MIME-Version' => '1.0',
            'Date' => date('r'),
            'Message-ID' => '<' . md5(uniqid()) . '@' . $this->localhost . '>',
            'X-Mailer' => 'miGears-Mail',
        ];
        if ($mail->to !== []) {
            $headers['To'] = implode(', ', $mail->to);
        }
        if ($mail->cc !== []) {
            $headers['Cc'] = implode(', ', $mail->cc);
        }
        if ($mail->replyTo !== '') {
            $headers['Reply-To'] = $mail->replyTo;
        }

        if ($boundary) {
            $headers['Content-Type'] = 'multipart/mixed; boundary="' . $boundary . '"';
        } else {
            $headers['Content-Type'] = $mail->getContentType() . '; charset=' . $this->stripCrlf($mail->charset);
            $headers['Content-Transfer-Encoding'] = 'base64';
        }

        // Custom headers are applied last so they can override built-in
        // ones of the same name — matching NativeMailer's behaviour.
        foreach ($mail->headers as $name => $value) {
            $headers[$name] = $value;
        }

        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $this->sanitizeHeaderName($name) . ': ' . $this->stripCrlf($value);
        }

        $body = "\r\n";
        if ($boundary) {
            $body .= '--' . $boundary . "\r\n";
            $body .= 'Content-Type: ' . $mail->getContentType() . '; charset=' . $this->stripCrlf($mail->charset) . "\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($mail->body)) . "\r\n";
            foreach ($mail->attachments as $att) {
                $body .= $this->buildAttachmentPart($att, $boundary, $mail->charset);
            }
            $body .= '--' . $boundary . '--';
        } else {
            $body .= chunk_split(base64_encode($mail->body));
        }

        return implode("\r\n", $lines) . "\r\n" . rtrim($body, "\r\n");
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

    private function cmd(SmtpTransport $transport, string $command, string $expected): string
    {
        $transport->write($command . "\r\n");
        return $this->expect($transport, $expected);
    }

    private function expect(SmtpTransport $transport, string $expectedCode): string
    {
        $response = '';
        $line = $transport->readLine();
        if ($line === false) {
            throw MailException::from('SMTP connection closed unexpectedly');
        }
        $response .= $line;
        $code = substr(trim($line), 0, 3);
        if ($code !== $expectedCode) {
            throw MailException::from(
                "SMTP error: expected $expectedCode, got $code (response: " . trim($line) . ')'
            );
        }

        // RFC 5321: continuation lines start with "CODE-"; the final line
        // starts with "CODE " (code + space). A bare "CODE\r\n" with no
        // text and no trailing space is also treated as final so we don't
        // swallow the next response and desynchronise.
        $sep = substr($line, 3, 1);
        if ($sep === ' ' || $sep === "\r" || $sep === "\n" || $sep === '') {
            return $response;
        }

        while (true) {
            $line = $transport->readLine();
            if ($line === false) {
                throw MailException::from('SMTP connection closed mid-response');
            }
            $response .= $line;
            $code = substr(trim($line), 0, 3);
            if ($code !== $expectedCode) {
                throw MailException::from(
                    "SMTP error: expected $expectedCode, got $code (response: " . trim($line) . ')'
                );
            }
            $sep = substr($line, 3, 1);
            if ($sep === ' ' || $sep === "\r" || $sep === "\n" || $sep === '') {
                break;
            }
        }

        return $response;
    }
}