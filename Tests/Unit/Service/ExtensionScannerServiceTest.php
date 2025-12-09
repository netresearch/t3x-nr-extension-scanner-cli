<?php

declare(strict_types=1);

namespace Netresearch\ExtensionScannerCli\Tests\Unit\Service;

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
            ['indicator' => 'strong', 'message' => 'Test 1'],
            ['indicator' => 'strong', 'message' => 'Test 2'],
            ['indicator' => 'weak', 'message' => 'Test 3'],
        ];

        $result = $this->subject->calculateStatistics($matches);

        self::assertSame(3, $result['total']);
        self::assertSame(2, $result['strong']);
        self::assertSame(1, $result['weak']);
    }

    #[Test]
    public function calculateStatisticsDefaultsToStrongWhenIndicatorMissing(): void
    {
        $matches = [
            ['message' => 'Test without indicator'],
        ];

        $result = $this->subject->calculateStatistics($matches);

        self::assertSame(1, $result['strong']);
        self::assertSame(0, $result['weak']);
    }

    #[Test]
    public function getAvailableMatcherClassesReturnsNonEmptyArray(): void
    {
        $result = $this->subject->getAvailableMatcherClasses();

        self::assertIsArray($result);
        self::assertNotEmpty($result);
    }
}
