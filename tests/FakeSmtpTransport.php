<?php

declare(strict_types=1);

namespace MiGears\Mail\Tests;

use MiGears\Mail\Transport\SmtpTransport;

/**
 * In-memory SmtpTransport for unit testing. Records written commands and
 * replays a fixed queue of server responses.
 */
final class FakeSmtpTransport implements SmtpTransport
{
    /** @var list<string> */
    public array $responses = [];

    /** @var list<string> */
    public array $written = [];

    public bool $tlsEnabled = false;
    public bool $closed = false;
    public int $connectCount = 0;

    /** @var list<string> */
    public array $connected = [];

    private int $offset = 0;

    public function connect(string $host, int $port, string $encryption, int $timeout): void
    {
        $this->connectCount++;
        $this->connected[] = sprintf('%s:%d (%s)', $host, $port, $encryption);
    }

    public function readLine(): string|false
    {
        return $this->offset < count($this->responses) ? $this->responses[$this->offset++] : false;
    }

    public function write(string $data): void
    {
        $this->written[] = $data;
    }

    public function enableTls(): void
    {
        $this->tlsEnabled = true;
    }

    public function close(): void
    {
        $this->closed = true;
    }

    public function payload(): string
    {
        return implode('', $this->written);
    }
}