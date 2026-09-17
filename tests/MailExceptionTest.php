<?php

declare(strict_types=1);

namespace MiGears\Mail\Tests;

use PHPUnit\Framework\TestCase;
use MiGears\Mail\Exception\MailException;

final class MailExceptionTest extends TestCase
{
    public function testFromCreatesInstance(): void
    {
        $e = MailException::from('Something went wrong');
        self::assertSame('Something went wrong', $e->getMessage());
        self::assertSame(0, $e->getCode());
    }

    public function testFromWithPrevious(): void
    {
        $prev = new \Exception('root cause');
        $e = MailException::from('wrapper', $prev);
        self::assertSame($prev, $e->getPrevious());
    }

    public function testExtendsRuntimeException(): void
    {
        $e = MailException::from('test');
        self::assertInstanceOf(\RuntimeException::class, $e);
    }

    public function testIsFinal(): void
    {
        $ref = new \ReflectionClass(MailException::class);
        self::assertTrue($ref->isFinal());
    }

    public function testCanBeThrown(): void
    {
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('boom');
        throw MailException::from('boom');
    }
}
