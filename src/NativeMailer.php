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

        $to = implode(', ', $mail->to);
        $subject = $mail->subject;
        $body = $mail->body;
        $headers = $this->buildHeaders($mail);

        $result = @mail($to, $subject, $body, $headers);

        if ($result === false) {
            throw MailException::from('Failed to send mail via native mail() function');
        }
    }

    protected function buildHeaders(Mail $mail): string
    {
        $headers = $mail->headers;

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
        $headers['Content-Type'] = sprintf('%s; charset=%s', $mail->getContentType(), $mail->charset);
        $headers['X-Mailer'] = 'miGears-Mail';

        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = sprintf('%s: %s', $name, $value);
        }

        return implode("\r\n", $lines);
    }
}
