<?php

declare(strict_types=1);

namespace MiGears\Mail\Transport;

use MiGears\Mail\Exception\MailException;

/**
 * Default SmtpTransport backed by fsockopen network socket.
 */
final class SocketSmtpTransport implements SmtpTransport
{
    /** @var resource|null */
    private $socket;

    public function connect(string $host, int $port, string $encryption, int $timeout): void
    {
        $fullHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;
        $socket = @fsockopen($fullHost, $port, $errno, $errstr, $timeout);
        if ($socket === false) {
            throw MailException::from("SMTP connection failed: $errstr ($errno)");
        }
        stream_set_timeout($socket, $timeout);
        $this->socket = $socket;
    }

    public function readLine(): string|false
    {
        return fgets($this->socket, 512);
    }

    public function write(string $data): void
    {
        $written = fwrite($this->socket, $data);
        if ($written === false || $written !== strlen($data)) {
            throw MailException::from('SMTP write failed');
        }
    }

    public function enableTls(): void
    {
        if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw MailException::from('Failed to enable TLS on SMTP connection');
        }
    }

    public function close(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
            $this->socket = null;
        }
    }
}