<?php

declare(strict_types=1);

namespace Netresearch\ExtensionScannerCli\Tests\Unit\Output;

use Netresearch\ExtensionScannerCli\Output\JsonOutputFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

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
                [
                    'file' => 'Classes/Test.php',
                    'line' => 10,
                    'indicator' => 'strong',
                    'message' => 'Test message',
                ],
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
}
