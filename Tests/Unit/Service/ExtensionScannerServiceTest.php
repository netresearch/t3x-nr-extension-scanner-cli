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
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
            GeneralUtility::rmdir($this->temporaryDirectory, true);
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
                $reported[$fileName] = $error;
            },
        );

        self::assertSame([], $matches);
        self::assertSame(['Broken.php'], array_keys($reported));
        self::assertNotSame('', $reported['Broken.php']);
    }

    #[Test]
    public function scanFileReportsANameResolutionErrorAndReturnsNoMatches(): void
    {
        $this->createTemporaryDirectory();
        $file = $this->writeScannedFile('DuplicateAlias.php', "<?php\nuse A\\B as X;\nuse C\\D as X;\n");
        $reported = [];

        $matches = $this->subject->scanFile(
            $file,
            null,
            [],
            static function (string $fileName, string $error) use (&$reported): void {
                $reported[$fileName] = $error;
            },
        );

        self::assertSame([], $matches);
        self::assertSame(['DuplicateAlias.php'], array_keys($reported));
        self::assertStringContainsString('already in use', $reported['DuplicateAlias.php']);
    }

    #[Test]
    public function scanPathSkipsADirectoryThatCannotBeRead(): void
    {
        if (\function_exists('posix_geteuid') && posix_geteuid() === 0) {
            self::markTestSkipped('File permissions do not restrict root.');
        }

        $directory = $this->createTemporaryDirectory();
        $this->writeScannedFile('Classes/Readable.php', '<?php function (' . "\n");
        $this->writeScannedFile('Locked/Hidden.php', '<?php function (' . "\n");
        chmod($directory . '/Locked', 0o000);
        $subject = new class extends ExtensionScannerService {
            public function getMatcherConfigurations(): array
            {
                return [];
            }
        };
        $reported = [];

        try {
            $subject->scanPath(
                $directory,
                null,
                static function (string $fileName, string $error) use (&$reported): void {
                    $reported[$fileName] = $error;
                },
            );
        } finally {
            chmod($directory . '/Locked', 0o755);
        }

        self::assertSame(['Classes/Readable.php'], array_keys($reported));
    }

    #[Test]
    public function scanFileReportsAFileThatCannotBeReadAndReturnsNoMatches(): void
    {
        $directory = $this->createTemporaryDirectory();
        $file = new SplFileInfo($directory . '/Classes/Missing.php', 'Classes', 'Classes/Missing.php');
        $reported = [];

        $matches = $this->subject->scanFile(
            $file,
            null,
            [],
            static function (string $fileName, string $error) use (&$reported): void {
                $reported[$fileName] = $error;
            },
        );

        self::assertSame([], $matches);
        self::assertSame(['Classes/Missing.php' => 'The file could not be read.'], $reported);
    }

    #[Test]
    public function scanPathSkipsDependencyDirectoriesButNotFilesWhoseNameContainsTheirName(): void
    {
        $directory = $this->createTemporaryDirectory();
        $scannedFiles = [
            'vendor/Package/Dependency.php',
            'Classes/node_modules/Module.php',
            'Classes/vendorApi.php',
            'Classes/vendorish/Helper.php',
        ];
        foreach ($scannedFiles as $scannedFile) {
            $this->writeScannedFile($scannedFile, '<?php function (' . "\n");
        }

        $subject = new class extends ExtensionScannerService {
            public function getMatcherConfigurations(): array
            {
                return [];
            }
        };
        $reported = [];

        $subject->scanPath(
            $directory,
            null,
            static function (string $fileName, string $error) use (&$reported): void {
                $reported[$fileName] = $error;
            },
        );
        $reportedFiles = array_keys($reported);
        sort($reportedFiles);

        self::assertSame(['Classes/vendorApi.php', 'Classes/vendorish/Helper.php'], $reportedFiles);
        self::assertNotContains('', $reported);
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
        if (!is_dir(\dirname($path))) {
            mkdir(\dirname($path), 0o777, true);
        }

        file_put_contents($path, $content);
        $relativePath = \dirname($relativePathname);

        return new SplFileInfo($path, $relativePath === '.' ? '' : $relativePath, $relativePathname);
    }
}
