<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Netresearch\ExtensionScannerCli\Command;

use Netresearch\ExtensionScannerCli\Output\CheckstyleOutputFormatter;
use Netresearch\ExtensionScannerCli\Output\JsonOutputFormatter;
use Netresearch\ExtensionScannerCli\Output\OutputFormatterInterface;
use Netresearch\ExtensionScannerCli\Output\TableOutputFormatter;
use Netresearch\ExtensionScannerCli\Service\ExtensionScannerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * CLI command to scan extensions for deprecated/removed TYPO3 API usage.
 *
 * This command provides the same functionality as the Install Tool's
 * Extension Scanner, but accessible from the command line for CI/CD integration.
 *
 * Usage examples:
 *   bin/typo3 extension:scan my_extension
 *   bin/typo3 extension:scan --path=/path/to/extension
 *   bin/typo3 extension:scan my_extension --format=json
 *   bin/typo3 extension:scan --all --format=checkstyle > report.xml
 */
class ExtensionScannerCommand extends Command
{
    private const SUPPORTED_FORMATS = ['table', 'json', 'checkstyle'];

    public function __construct(
        private readonly ExtensionScannerService $scannerService,
        private readonly PackageManager $packageManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Scan extension code for deprecated/removed TYPO3 API usage')
            ->setHelp(
                'This command scans PHP files in extensions for usage of deprecated or removed ' .
                'TYPO3 Core API. It can be used in CI/CD pipelines to detect compatibility issues.' . LF . LF .
                'Examples:' . LF .
                '  <info>bin/typo3 extension:scan my_extension</info>' . LF .
                '  <info>bin/typo3 extension:scan --path=/path/to/extension</info>' . LF .
                '  <info>bin/typo3 extension:scan my_extension --format=json</info>' . LF .
                '  <info>bin/typo3 extension:scan --all --format=checkstyle > report.xml</info>'
            )
            ->addArgument(
                'extensions',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Extension key(s) to scan. If not provided, use --path or --all.',
                []
            )
            ->addOption(
                'path',
                'p',
                InputOption::VALUE_REQUIRED,
                'Custom path to scan (alternative to extension key)'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Scan all loaded third-party extensions'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format: table (default), json, checkstyle',
                'table'
            )
            ->addOption(
                'no-progress',
                null,
                InputOption::VALUE_NONE,
                'Disable progress output'
            )
            ->addOption(
                'fail-on-weak',
                null,
                InputOption::VALUE_NONE,
                'Return non-zero exit code on weak matches (by default only strong matches cause failure)'
            )
            ->addOption(
                'include-system',
                null,
                InputOption::VALUE_NONE,
                'Include TYPO3 system extensions when using --all'
            )
            ->addOption(
                'verbose-parse-errors',
                null,
                InputOption::VALUE_NONE,
                'Show parse errors for files that cannot be analyzed'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Ensure full TYPO3 is bootstrapped for package manager access
        Bootstrap::initializeBackendAuthentication();

        $extensions = (array)$input->getArgument('extensions');
        $customPath = $input->getOption('path');
        $scanAll = $input->getOption('all');
        $format = $input->getOption('format');
        $noProgress = $input->getOption('no-progress');
        $failOnWeak = $input->getOption('fail-on-weak');
        $includeSystem = $input->getOption('include-system');
        $verboseParseErrors = $input->getOption('verbose-parse-errors');

        // Validate format option
        if (!in_array($format, self::SUPPORTED_FORMATS, true)) {
            $io->error(sprintf('Invalid format "%s". Use: table, json, or checkstyle', $format));
            return Command::FAILURE;
        }

        // Determine paths to scan
        $pathsToScan = $this->resolvePathsToScan(
            $extensions,
            $customPath,
            $scanAll,
            $includeSystem,
            $io
        );

        if ($pathsToScan === null) {
            return Command::FAILURE;
        }

        if (empty($pathsToScan)) {
            $io->warning('No extensions found to scan');
            return Command::SUCCESS;
        }

        // Scan each path
        $allMatches = [];
        $totalStrong = 0;
        $totalWeak = 0;

        foreach ($pathsToScan as $extensionKey => $path) {
            if (!$noProgress && $format === 'table') {
                $io->section(sprintf('Scanning: %s', $extensionKey));
            }

            $extensionMatches = $this->scanExtensionPath(
                $path,
                $io,
                $noProgress,
                $format,
                $verboseParseErrors
            );
            $allMatches[$extensionKey] = $extensionMatches;

            $stats = $this->scannerService->calculateStatistics($extensionMatches);
            $totalStrong += $stats['strong'];
            $totalWeak += $stats['weak'];
        }

        // Output results using appropriate formatter
        $formatter = $this->createFormatter($format, $io);
        $formatter->format($output, $allMatches, $totalStrong, $totalWeak);

        // Determine exit code
        if ($totalStrong > 0) {
            return Command::FAILURE;
        }
        if ($failOnWeak && $totalWeak > 0) {
            return 2; // Custom exit code for weak-only matches
        }

        return Command::SUCCESS;
    }

    /**
     * Resolve which paths to scan based on input options.
     *
     * @param array<string> $extensions
     * @return array<string, string>|null Paths indexed by extension key, or null on error
     */
    private function resolvePathsToScan(
        array $extensions,
        ?string $customPath,
        bool $scanAll,
        bool $includeSystem,
        SymfonyStyle $io
    ): ?array {
        $pathsToScan = [];

        if ($customPath !== null) {
            if (!is_dir($customPath)) {
                $io->error(sprintf('Path does not exist: %s', $customPath));
                return null;
            }
            $pathsToScan['custom'] = rtrim($customPath, '/');
        } elseif ($scanAll) {
            foreach ($this->packageManager->getActivePackages() as $package) {
                // Skip system extensions (typo3/cms-* packages) unless --include-system is set
                if (!$includeSystem && $package->getValueFromComposerManifest('type') === 'typo3-cms-framework') {
                    continue;
                }
                $pathsToScan[$package->getPackageKey()] = $package->getPackagePath();
            }
        } elseif (!empty($extensions)) {
            foreach ($extensions as $extensionKey) {
                if (!$this->packageManager->isPackageActive($extensionKey)) {
                    $io->error(sprintf('Extension not found or not active: %s', $extensionKey));
                    return null;
                }
                $package = $this->packageManager->getPackage($extensionKey);
                $pathsToScan[$extensionKey] = $package->getPackagePath();
            }
        } else {
            $io->error('Please provide extension key(s), --path, or --all option');
            return null;
        }

        return $pathsToScan;
    }

    /**
     * Scan a single extension path.
     *
     * @return array<int, array<string, mixed>>
     */
    private function scanExtensionPath(
        string $path,
        SymfonyStyle $io,
        bool $noProgress,
        string $format,
        bool $verboseParseErrors
    ): array {
        $progressCallback = null;
        if (!$noProgress && $format === 'table') {
            $progressCallback = static function (int $current, int $total) use ($io): void {
                $io->write(sprintf("\rProcessed %d/%d files", $current, $total));
            };
        }

        $parseErrorCallback = null;
        if ($verboseParseErrors) {
            $parseErrorCallback = static function (string $file, string $error) use ($io): void {
                $io->warning(sprintf('Parse error in %s: %s', $file, $error));
            };
        }

        $matches = $this->scannerService->scanPath($path, $progressCallback, $parseErrorCallback);

        if (!$noProgress && $format === 'table') {
            $io->newLine();
        }

        return $matches;
    }

    /**
     * Create the appropriate output formatter for the requested format.
     */
    private function createFormatter(string $format, SymfonyStyle $io): OutputFormatterInterface
    {
        return match ($format) {
            'json' => new JsonOutputFormatter(),
            'checkstyle' => new CheckstyleOutputFormatter(),
            default => new TableOutputFormatter($io),
        };
    }
}
