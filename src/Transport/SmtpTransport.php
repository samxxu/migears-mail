<?php

declare(strict_types=1);

namespace MiGears\Mail\Transport;

/**
 * Minimal SMTP socket abstraction. Injected into SmtpMailer so tests can
 * use a fake implementation instead of a real network socket.
 */
interface SmtpTransport
{
    public function connect(string $host, int $port, string $encryption, int $timeout): void;

    /** @return string|false One line of response, or false on EOF. */
    public function readLine(): string|false;

    public function write(string $data): void;

    public function enableTls(): void;

    public function close(): void;
}