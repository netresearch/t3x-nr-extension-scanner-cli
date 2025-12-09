<?php

declare(strict_types=1);

namespace Netresearch\ExtensionScannerCli\Tests\Unit\Output;

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
                [
                    'file' => 'Classes/Test.php',
                    'absolutePath' => '/path/to/Classes/Test.php',
                    'line' => 10,
                    'indicator' => 'strong',
                    'message' => 'Test message',
                    'matcherClass' => 'TestMatcher',
                ],
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
                [
                    'absolutePath' => '/path/to/File1.php',
                    'line' => 10,
                    'indicator' => 'strong',
                    'message' => 'Message 1',
                ],
            ],
            'ext2' => [
                [
                    'absolutePath' => '/path/to/File2.php',
                    'line' => 20,
                    'indicator' => 'weak',
                    'message' => 'Message 2',
                ],
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
                [
                    'absolutePath' => '/path/to/File.php',
                    'line' => 10,
                    'indicator' => 'strong',
                    'message' => 'Strong match',
                ],
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
                [
                    'absolutePath' => '/path/to/File.php',
                    'line' => 10,
                    'indicator' => 'weak',
                    'message' => 'Weak match',
                ],
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
                [
                    'absolutePath' => '/path/to/File.php',
                    'line' => 10,
                    'indicator' => 'strong',
                    'message' => 'Message with <special> & "characters"',
                ],
            ],
        ];

        $this->subject->format($output, $matches, 1, 0);

        $xml = $output->fetch();
        self::assertStringContainsString('&lt;special&gt;', $xml);
        self::assertStringContainsString('&amp;', $xml);
    }
}
