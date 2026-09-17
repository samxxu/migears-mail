<?php

declare(strict_types=1);

namespace MiGears\Mail\Tests;

use MiGears\Mail\Mail;
use MiGears\Mail\MailerInterface;

/**
 * In-memory mailer for unit testing.
 * Stores all sent emails in memory for easy assertions.
 */
final class InMemoryMailer implements MailerInterface
{
    /** @var list<Mail> */
    private array $mails = [];

    public function send(Mail $mail): void
    {
        $this->mails[] = $mail;
    }

    /**
     * @return list<Mail>
     */
    public function getSentMails(): array
    {
        return $this->mails;
    }

    public function getSentCount(): int
    {
        return count($this->mails);
    }

    public function getLastMail(): ?Mail
    {
        return $this->mails !== [] ? end($this->mails) : null;
    }

    public function clear(): void
    {
        $this->mails = [];
    }
}
