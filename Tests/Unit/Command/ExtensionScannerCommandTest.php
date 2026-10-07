<?php

declare(strict_types=1);

/*
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

namespace Netresearch\ExtensionScannerCli\Tests\Unit\Command;

use Netresearch\ExtensionScannerCli\Command\ExtensionScannerCommand;
use Netresearch\ExtensionScannerCli\Dto\ScanMatch;
use Netresearch\ExtensionScannerCli\Service\ExtensionScannerService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Package\PackageManager;

#[CoversClass(ExtensionScannerCommand::class)]
final class ExtensionScannerCommandTest extends TestCase
{
    private const FILE = 'Classes/<info>Tagged</info>.php';

    #[Test]
    public function jsonOutputStaysParseableWhenParseErrorsAreReported(): void
    {
        $tester = $this->createTester();

        $exitCode = $tester->execute(
            ['--path' => __DIR__, '--format' => 'json', '--verbose-parse-errors' => true],
            ['capture_stderr_separately' => true],
        );

        self::assertSame(1, $exitCode);
        $result = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($result);
        self::assertSame(self::FILE, $result['extensions'][0]['matches'][0]['file']);
        self::assertStringContainsString('Cannot analyse', $tester->getErrorOutput());
    }

    #[Test]
    public function checkstyleOutputStaysParseableWhenParseErrorsAreReported(): void
    {
        $tester = $this->createTester();

        $tester->execute(
            ['--path' => __DIR__, '--format' => 'checkstyle', '--verbose-parse-errors' => true],
            ['capture_stderr_separately' => true],
        );

        self::assertNotFalse(simplexml_load_string($tester->getDisplay()));
        self::assertStringContainsString('Cannot analyse', $tester->getErrorOutput());
    }

    #[Test]
    public function parseErrorsAreShownWithoutControlCharacters(): void
    {
        $tester = $this->createTester();

        $tester->execute(
            ['--path' => __DIR__, '--verbose-parse-errors' => true],
            ['capture_stderr_separately' => true, 'decorated' => false],
        );

        $messages = $tester->getErrorOutput();
        self::assertStringContainsString('Broken<error>.php', $messages);
        self::assertStringNotContainsString("\x1b", $messages);
        self::assertStringNotContainsString("\x1b", $tester->getDisplay());
    }

    private function createTester(): CommandTester
    {
        $service = $this->createStub(ExtensionScannerService::class);
        $service->method('scanPath')->willReturnCallback(
            static function (string $path, ?callable $progress = null, ?callable $parseError = null): array {
                if ($parseError !== null) {
                    $parseError("Broken<error>.php\x1b]0;title\x07", "Syntax error\x1b[2J, unexpected '<'");
                }

                return [
                    new ScanMatch(self::FILE, '/var/www/ext/' . self::FILE, 3, 'strong', 'Test message', 'TestMatcher'),
                ];
            },
        );
        $service->method('calculateStatistics')->willReturn(['total' => 1, 'strong' => 1, 'weak' => 0]);

        $command = new class($service, $this->createStub(PackageManager::class)) extends ExtensionScannerCommand {
            protected function initializeBackendAuthentication(): void
            {
                // Intentionally empty: the unit test runs without a database,
                // and the scan does not use the backend user.
            }
        };
        $command->setName('extension:scan');

        return new CommandTester($command);
    }
}
