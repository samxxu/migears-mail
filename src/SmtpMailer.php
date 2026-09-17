<?php

declare(strict_types=1);

namespace MiGears\Mail;

use MiGears\Mail\Exception\MailException;

/**
 * SMTP mail sender implementation, directly implementing the SMTP protocol using fsockopen.
 * Supports LOGIN authentication, TLS/SSL encryption, attachments, and HTML emails.
 */
class SmtpMailer implements MailerInterface
{
    /** @var resource|null */
    private $socket;
    private string $localhost;

    public function __construct(
        private readonly string $host,
        private readonly int $port = 25,
        private readonly string $username = '',
        private readonly string $password = '',
        private readonly string $encryption = '', // '', 'tls', 'ssl'
        private readonly int $timeout = 30,
    ) {
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

        try {
            $this->connect();
            $this->ehlo();
            $this->authenticate();
            $this->cmd('MAIL FROM:<' . $mail->from . '>', '250');
            foreach (array_merge($mail->to, $mail->cc, $mail->bcc) as $rcpt) {
                $this->cmd('RCPT TO:<' . $rcpt . '>', '250');
            }
            $this->cmd('DATA', '354');
            fwrite($this->socket, str_replace("\r\n.", "\r\n..", $this->buildMessage($mail)) . "\r\n");
            $this->cmd('.', '250');
            $this->cmd('QUIT', '221');
        } finally {
            $this->disconnect();
        }
    }

    private function connect(): void
    {
        $host = $this->encryption === 'ssl' ? 'ssl://' . $this->host : $this->host;
        $this->socket = @fsockopen($host, $this->port, $errno, $errstr, $this->timeout);
        if ($this->socket === false) {
            throw MailException::from("SMTP connection failed: $errstr ($errno)");
        }
        stream_set_timeout($this->socket, $this->timeout);
        $this->expect('220');
    }

    private function ehlo(): void
    {
        $response = $this->cmd('EHLO ' . $this->localhost, '250');
        if ($this->encryption === 'tls' && str_contains($response, '250-STARTTLS')) {
            $this->cmd('STARTTLS', '220');
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->cmd('EHLO ' . $this->localhost, '250');
        }
    }

    private function authenticate(): void
    {
        if ($this->username === '' || $this->password === '') {
            return;
        }
        $this->cmd('AUTH LOGIN', '334');
        $this->cmd(base64_encode($this->username), '334');
        $this->cmd(base64_encode($this->password), '235');
    }

    private function buildMessage(Mail $mail): string
    {
        $boundary = $mail->hasAttachments() ? '----=_Part_' . md5(uniqid()) : null;
        $headers = [
            'From' => $mail->getFormattedFrom(),
            'To' => implode(', ', $mail->to),
            'Subject' => $this->encodeSubject($mail->subject, $mail->charset),
            'MIME-Version' => '1.0',
            'Date' => date('r'),
            'Message-ID' => '<' . md5(uniqid()) . '@' . $this->localhost . '>',
            'X-Mailer' => 'miGears-Mail',
        ];
        if ($mail->cc !== []) {
            $headers['Cc'] = implode(', ', $mail->cc);
        }
        if ($mail->replyTo !== '') {
            $headers['Reply-To'] = $mail->replyTo;
        }
        foreach ($mail->headers as $name => $value) {
            $headers[$name] = $value;
        }

        if ($boundary) {
            $headers['Content-Type'] = 'multipart/mixed; boundary="' . $boundary . '"';
        } else {
            $headers['Content-Type'] = $mail->getContentType() . '; charset=' . $mail->charset;
            $headers['Content-Transfer-Encoding'] = 'base64';
        }

        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = "$name: $value";
        }

        $body = "\r\n";
        if ($boundary) {
            $body .= '--' . $boundary . "\r\n";
            $body .= 'Content-Type: ' . $mail->getContentType() . '; charset=' . $mail->charset . "\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($mail->body)) . "\r\n";
            foreach ($mail->attachments as $att) {
                $body .= $this->buildAttachmentPart($att, $boundary);
            }
            $body .= '--' . $boundary . '--';
        } else {
            $body .= chunk_split(base64_encode($mail->body));
        }

        return implode("\r\n", $lines) . "\r\n" . rtrim($body, "\r\n");
    }

    /** @param array{path: string, name: ?string, type: ?string} $att */
    private function buildAttachmentPart(array $att, string $boundary): string
    {
        $filePath = $att['path'];
        $fileName = $att['name'] ?? basename($filePath);
        $mimeType = $att['type'] ?? 'application/octet-stream';

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

    private function encodeSubject(string $subject, string $charset): string
    {
        return preg_match('/[^\x20-\x7E]/', $subject)
            ? sprintf('=?%s?B?%s?=', $charset, base64_encode($subject))
            : $subject;
    }

    private function cmd(string $command, string $expected): string
    {
        fwrite($this->socket, $command . "\r\n");
        return $this->expect($expected);
    }

    private function expect(string $expectedCode): string
    {
        $response = '';
        $line = false;
        while (($line = fgets($this->socket, 512)) !== false) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        if ($line === false && $response === '') {
            throw MailException::from('SMTP connection closed unexpectedly');
        }
        $code = substr(trim($response), 0, 3);
        if ($code !== $expectedCode) {
            throw MailException::from(
                "SMTP error: expected $expectedCode, got $code - " . trim($response)
            );
        }
        return $response;
    }

    private function disconnect(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
            $this->socket = null;
        }
    }
}
