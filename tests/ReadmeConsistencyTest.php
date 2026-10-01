<?php

declare(strict_types=1);

namespace MiGears\Mail\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Guards the README's statements about custom headers overriding same-named
 * built-ins against the two driver divergences this module documents.
 *
 * P1-2: the README used to claim, without qualification, that custom headers
 * override same-named built-ins "in both drivers". For `To`/`Subject` the
 * native driver appends the custom header instead — `mail()` still receives
 * the real recipients and subject as its own arguments — so the blanket claim
 * was false. P3-8: it used to claim SmtpMailer "never writes a `Bcc:` header";
 * a custom `Bcc` is in fact written through verbatim. Both halves are pinned
 * so a wording change cannot silently restore the false absolute claims.
 */
final class ReadmeConsistencyTest extends TestCase
{
    /** @return array{string, string} the English and Chinese README halves. */
    private static function halves(): array
    {
        $readme = (string) file_get_contents(dirname(__DIR__) . '/README.md');
        $halves = explode("\n---\n", $readme);
        self::assertCount(2, $halves, 'the README is no longer in two halves');

        return [$halves[0], $halves[1]];
    }

    public function testBothHalvesSayACustomToSubjectIsAppendedByTheNativeDriver(): void
    {
        [$en, $zh] = self::halves();

        // The old blanket claim must not come back.
        self::assertStringNotContainsString(
            'Custom headers override built-in ones of the same name in both drivers.',
            $en
        );
        self::assertStringNotContainsString('两个驱动中同名自定义头均会覆盖内置头', $zh);

        self::assertMatchesRegularExpression('/custom `To`\/`Subject` header is appended/', $en);
        self::assertMatchesRegularExpression('/自定义 `To`\/`Subject` 头会被追加/u', $zh);
    }

    public function testBothHalvesSaySmtpMailerWritesNoBuiltInBccHeader(): void
    {
        [$en, $zh] = self::halves();

        // The old absolute claim must not come back.
        self::assertStringNotContainsString('never writes a `Bcc:` header', $en);
        self::assertStringNotContainsString('绝不写出 `Bcc:` 头', $zh);

        self::assertMatchesRegularExpression('/writes no built-in `Bcc:` header/', $en);
        self::assertMatchesRegularExpression('/本身不写内置 `Bcc:` 头/u', $zh);
    }
}
