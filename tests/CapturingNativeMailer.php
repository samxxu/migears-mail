<?php

declare(strict_types=1);

namespace MiGears\Mail\Tests;

use MiGears\Mail\NativeMailer;

/**
 * NativeMailer test double that captures the exact arguments NativeMailer
 * would hand to mail(), instead of actually invoking it.
 */
final class CapturingNativeMailer extends NativeMailer
{
    public string $to = '';
    public string $subject = '';
    public string $body = '';
    public string $headers = '';
    public bool $result = true;

    protected function deliver(string $to, string $subject, string $body, string $headers): bool
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
        $this->headers = $headers;

        return $this->result;
    }
}
