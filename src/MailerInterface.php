<?php

declare(strict_types=1);

namespace MiGears\Mail;

use MiGears\Mail\Exception\MailException;

interface MailerInterface
{
    /**
     * @throws MailException
     */
    public function send(Mail $mail): void;
}
