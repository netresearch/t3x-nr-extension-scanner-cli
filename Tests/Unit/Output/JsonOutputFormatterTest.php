<?php

declare(strict_types=1);

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

namespace Netresearch\ExtensionScannerCli\Tests\Unit\Output;

use Netresearch\ExtensionScannerCli\Dto\ScanMatch;
use Netresearch\ExtensionScannerCli\Output\JsonOutputFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodCallMatcher;

#[CoversClass(JsonOutputFormatter::class)]
final class JsonOutputFormatterTest extends TestCase
{
    private JsonOutputFormatter $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new JsonOutputFormatter();
    }

    #[Test]
    public function formatOutputsValidJson(): void
    {
        $output = new BufferedOutput();
        $matches = [
            'test_extension' => [
                new ScanMatch(
                    'Classes/Test.php',
                    '/path/to/Classes/Test.php',
                    10,
                    'strong',
                    'Test message',
                    'TestMatcher',
                ),
            ],
        ];

        $this->subject->format($output, $matches, 1, 0);

        $result = json_decode($output->fetch(), true);
        self::assertIsArray($result);
        self::assertArrayHasKey('summary', $result);
        self::assertArrayHasKey('extensions', $result);
    }

    #[Test]
    public function formatIncludesCorrectSummary(): void
    {
        $output = new BufferedOutput();

        $this->subject->format($output, [], 5, 3);

        $result = json_decode($output->fetch(), true);
        self::assertSame(8, $result['summary']['total']);
        self::assertSame(5, $result['summary']['strong']);
        self::assertSame(3, $result['summary']['weak']);
    }

    #[Test]
    public function formatIncludesTimestamp(): void
    {
        $output = new BufferedOutput();

        $this->subject->format($output, [], 0, 0);

        $result = json_decode($output->fetch(), true);
        self::assertArrayHasKey('timestamp', $result['summary']);
        self::assertNotEmpty($result['summary']['timestamp']);
    }

    #[Test]
    public function formatIncludesMatchDetails(): void
    {
        $output = new BufferedOutput();
        $matches = [
            'my_extension' => [
                new ScanMatch(
                    'Classes/Service.php',
                    '/var/www/ext/Classes/Service.php',
                    42,
                    'strong',
                    'Deprecated API usage',
                    MethodCallMatcher::class,
                    ['Deprecation-12345.rst'],
                ),
            ],
        ];

        $this->subject->format($output, $matches, 1, 0);

        $result = json_decode($output->fetch(), true);
        $extensionMatches = $result['extensions'][0]['matches'][0];

        self::assertSame('Classes/Service.php', $extensionMatches['file']);
        self::assertSame(42, $extensionMatches['line']);
        self::assertSame('strong', $extensionMatches['indicator']);
        self::assertSame('Deprecated API usage', $extensionMatches['message']);
        self::assertSame('MethodCallMatcher', $extensionMatches['matcherClass']);
        self::assertContains('Deprecation-12345.rst', $extensionMatches['restFiles']);
    }

    #[Test]
    public function fileNamesWithConsoleTagsAreWrittenUnchanged(): void
    {
        foreach ([false, true] as $decorated) {
            $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, $decorated);
            $file = 'Classes/<info>Tagged</info>.php';
            $matches = [
                'test_extension' => [
                    new ScanMatch($file, '/var/www/ext/' . $file, 3, 'strong', 'Test message', 'TestMatcher'),
                ],
            ];

            $this->subject->format($output, $matches, 1, 0);

            $result = json_decode($output->fetch(), true, 512, JSON_THROW_ON_ERROR);
            self::assertIsArray($result);
            self::assertSame($file, $result['extensions'][0]['matches'][0]['file']);
        }
    }
}
