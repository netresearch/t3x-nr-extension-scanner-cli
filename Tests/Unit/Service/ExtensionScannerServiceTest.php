<?php

declare(strict_types=1);

namespace Netresearch\ExtensionScannerCli\Tests\Unit\Service;

use Netresearch\ExtensionScannerCli\Dto\ScanMatch;
use Netresearch\ExtensionScannerCli\Service\ExtensionScannerService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExtensionScannerService::class)]
final class ExtensionScannerServiceTest extends TestCase
{
    private ExtensionScannerService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new ExtensionScannerService();
    }

    #[Test]
    public function calculateStatisticsReturnsZeroForEmptyMatches(): void
    {
        $result = $this->subject->calculateStatistics([]);

        self::assertSame(0, $result['total']);
        self::assertSame(0, $result['strong']);
        self::assertSame(0, $result['weak']);
    }

    #[Test]
    public function calculateStatisticsCountsStrongMatchesCorrectly(): void
    {
        $matches = [
            new ScanMatch('file1.php', '/path/file1.php', 10, 'strong', 'Test 1', 'TestMatcher'),
            new ScanMatch('file2.php', '/path/file2.php', 20, 'strong', 'Test 2', 'TestMatcher'),
            new ScanMatch('file3.php', '/path/file3.php', 30, 'weak', 'Test 3', 'TestMatcher'),
        ];

        $result = $this->subject->calculateStatistics($matches);

        self::assertSame(3, $result['total']);
        self::assertSame(2, $result['strong']);
        self::assertSame(1, $result['weak']);
    }

    #[Test]
    public function calculateStatisticsCountsAllWeakMatches(): void
    {
        $matches = [
            new ScanMatch('file1.php', '/path/file1.php', 10, 'weak', 'Test 1', 'TestMatcher'),
            new ScanMatch('file2.php', '/path/file2.php', 20, 'weak', 'Test 2', 'TestMatcher'),
        ];

        $result = $this->subject->calculateStatistics($matches);

        self::assertSame(2, $result['total']);
        self::assertSame(0, $result['strong']);
        self::assertSame(2, $result['weak']);
    }

    #[Test]
    public function getAvailableMatcherClassesReturnsNonEmptyArray(): void
    {
        $result = $this->subject->getAvailableMatcherClasses();

        self::assertIsArray($result);
        self::assertNotEmpty($result);
    }
}
