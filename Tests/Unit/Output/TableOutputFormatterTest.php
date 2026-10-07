<?php

declare(strict_types=1);

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

namespace Netresearch\ExtensionScannerCli\Tests\Unit\Output;

use Netresearch\ExtensionScannerCli\Dto\ScanMatch;
use Netresearch\ExtensionScannerCli\Output\TableOutputFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodCallMatcher;

#[CoversClass(TableOutputFormatter::class)]
final class TableOutputFormatterTest extends TestCase
{
    #[Test]
    public function fileNamesAndMessagesAreShownLiterally(): void
    {
        $output = new BufferedOutput();
        $subject = new TableOutputFormatter(new SymfonyStyle(new ArrayInput([]), $output));
        $matches = [
            'ext<info>key</info>' => [
                new ScanMatch(
                    "Classes/<error>x</error>\x1b]0;title\x07.php",
                    '/var/www/ext/Classes/x.php',
                    7,
                    'strong',
                    "Call to <comment>removed</comment>\x1b[2J method",
                    MethodCallMatcher::class,
                ),
            ],
        ];

        $subject->format($output, $matches, 1, 0);
        $text = $output->fetch();

        self::assertStringContainsString('ext<info>key</info>', $text);
        self::assertStringContainsString('Classes/<error>x</error>?]0;title?.php', $text);
        self::assertStringContainsString('Call to <comment>removed</comment>?[2J method', $text);
        self::assertStringNotContainsString("\x1b", $text);
        self::assertStringNotContainsString("\x07", $text);
    }
}
