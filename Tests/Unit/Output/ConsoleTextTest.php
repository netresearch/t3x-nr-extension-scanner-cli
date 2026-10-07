<?php

declare(strict_types=1);

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

namespace Netresearch\ExtensionScannerCli\Tests\Unit\Output;

use Netresearch\ExtensionScannerCli\Output\ConsoleText;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatter;

#[CoversClass(ConsoleText::class)]
final class ConsoleTextTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function controlCharacterProvider(): iterable
    {
        yield 'escape sequence' => ["Classes/\x1b[2JTest.php", 'Classes/?[2JTest.php'];
        yield 'terminal title' => ["a\x1b]0;title\x07b", 'a?]0;title?b'];
        yield 'NUL, DEL and newline' => ["a\x00b\x7fc\nd", 'a?b?c?d'];
        yield 'C1 control (CSI)' => ["a\u{9B}31mb", 'a?31mb'];
    }

    #[Test]
    #[DataProvider('controlCharacterProvider')]
    public function withoutControlCharactersReplacesControlCharacters(string $text, string $expected): void
    {
        self::assertSame($expected, ConsoleText::withoutControlCharacters($text));
    }

    #[Test]
    public function withoutControlCharactersKeepsPrintableUnicode(): void
    {
        self::assertSame('Classes/Ü/Ärger-€.php', ConsoleText::withoutControlCharacters('Classes/Ü/Ärger-€.php'));
    }

    #[Test]
    public function forFormattedOutputIsShownLiterallyByTheConsoleFormatter(): void
    {
        $text = ConsoleText::forFormattedOutput("Classes/<error>x</error>\x1b.php");

        self::assertSame('Classes/<error>x</error>?.php', (new OutputFormatter(true))->format($text));
    }
}
