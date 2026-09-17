<?php

declare(strict_types=1);

namespace MiGears\Mail\Exception;

final class MailException extends \RuntimeException
{
    public static function from(string $message, ?\Throwable $previous = null): self
    {
        return new self($message, 0, $previous);
    }
}
