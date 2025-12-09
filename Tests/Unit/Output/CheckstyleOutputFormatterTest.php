<?php

declare(strict_types=1);

namespace Netresearch\ExtensionScannerCli\Tests\Unit\Output;

use Netresearch\ExtensionScannerCli\Dto\ScanMatch;
use Netresearch\ExtensionScannerCli\Output\CheckstyleOutputFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

#[CoversClass(CheckstyleOutputFormatter::class)]
final class CheckstyleOutputFormatterTest extends TestCase
{
    private CheckstyleOutputFormatter $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new CheckstyleOutputFormatter();
    }

    #[Test]
    public function formatOutputsValidXml(): void
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

        $xml = $output->fetch();
        self::assertStringContainsString('<?xml version="1.0"', $xml);
        self::assertStringContainsString('<checkstyle', $xml);
        self::assertStringContainsString('</checkstyle>', $xml);
    }

    #[Test]
    public function formatCreatesFileElementsForEachFile(): void
    {
        $output = new BufferedOutput();
        $matches = [
            'ext1' => [
                new ScanMatch(
                    'File1.php',
                    '/path/to/File1.php',
                    10,
                    'strong',
                    'Message 1',
                    'TestMatcher',
                ),
            ],
            'ext2' => [
                new ScanMatch(
                    'File2.php',
                    '/path/to/File2.php',
                    20,
                    'weak',
                    'Message 2',
                    'TestMatcher',
                ),
            ],
        ];

        $this->subject->format($output, $matches, 1, 1);

        $xml = $output->fetch();
        self::assertStringContainsString('name="/path/to/File1.php"', $xml);
        self::assertStringContainsString('name="/path/to/File2.php"', $xml);
    }

    #[Test]
    public function formatUsesErrorSeverityForStrongMatches(): void
    {
        $output = new BufferedOutput();
        $matches = [
            'ext' => [
                new ScanMatch(
                    'File.php',
                    '/path/to/File.php',
                    10,
                    'strong',
                    'Strong match',
                    'TestMatcher',
                ),
            ],
        ];

        $this->subject->format($output, $matches, 1, 0);

        self::assertStringContainsString('severity="error"', $output->fetch());
    }

    #[Test]
    public function formatUsesWarningSeverityForWeakMatches(): void
    {
        $output = new BufferedOutput();
        $matches = [
            'ext' => [
                new ScanMatch(
                    'File.php',
                    '/path/to/File.php',
                    10,
                    'weak',
                    'Weak match',
                    'TestMatcher',
                ),
            ],
        ];

        $this->subject->format($output, $matches, 0, 1);

        self::assertStringContainsString('severity="warning"', $output->fetch());
    }

    #[Test]
    public function formatEscapesSpecialXmlCharacters(): void
    {
        $output = new BufferedOutput();
        $matches = [
            'ext' => [
                new ScanMatch(
                    'File.php',
                    '/path/to/File.php',
                    10,
                    'strong',
                    'Message with <special> & "characters"',
                    'TestMatcher',
                ),
            ],
        ];

        $this->subject->format($output, $matches, 1, 0);

        $xml = $output->fetch();
        self::assertStringContainsString('&lt;special&gt;', $xml);
        self::assertStringContainsString('&amp;', $xml);
    }
}
