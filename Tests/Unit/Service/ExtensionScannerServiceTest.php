<?php

declare(strict_types=1);

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

namespace Netresearch\ExtensionScannerCli\Tests\Unit\Service;

use Netresearch\ExtensionScannerCli\Dto\ScanMatch;
use Netresearch\ExtensionScannerCli\Service\ExtensionScannerService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

#[CoversClass(ExtensionScannerService::class)]
final class ExtensionScannerServiceTest extends TestCase
{
    private ExtensionScannerService $subject;

    private ?string $temporaryDirectory = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new ExtensionScannerService();
    }

    protected function tearDown(): void
    {
        if ($this->temporaryDirectory !== null) {
            foreach (glob($this->temporaryDirectory . '/*') ?: [] as $file) {
                unlink($file);
            }

            rmdir($this->temporaryDirectory);
            $this->temporaryDirectory = null;
        }

        parent::tearDown();
    }

    #[Test]
    public function scanFileParsesTheScannedCodeWithoutExecutingIt(): void
    {
        $directory = $this->createTemporaryDirectory();
        $marker = $directory . '/executed.marker';
        $file = $this->writeScannedFile(
            'SideEffect.php',
            '<?php' . "\n" . 'file_put_contents(' . var_export($marker, true) . ', "executed");' . "\n",
        );

        $matches = $this->subject->scanFile($file, null, []);

        self::assertSame([], $matches);
        self::assertFileDoesNotExist($marker);
    }

    #[Test]
    public function scanFileReportsAParseErrorAndReturnsNoMatches(): void
    {
        $this->createTemporaryDirectory();
        $file = $this->writeScannedFile('Broken.php', '<?php function (' . "\n");
        $reported = [];

        $matches = $this->subject->scanFile(
            $file,
            null,
            [],
            static function (string $fileName, string $error) use (&$reported): void {
                $reported[] = $fileName;
            },
        );

        self::assertSame([], $matches);
        self::assertSame(['Broken.php'], $reported);
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

    private function createTemporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/nr-extension-scanner-cli-' . bin2hex(random_bytes(8));
        mkdir($directory);
        $this->temporaryDirectory = $directory;

        return $directory;
    }

    private function writeScannedFile(string $relativePathname, string $content): SplFileInfo
    {
        self::assertNotNull($this->temporaryDirectory);
        $path = $this->temporaryDirectory . '/' . $relativePathname;
        file_put_contents($path, $content);

        return new SplFileInfo($path, '', $relativePathname);
    }
}
