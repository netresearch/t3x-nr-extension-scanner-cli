<?php

declare(strict_types=1);

/*
 * This file is part of the "nr_extension_scanner_cli" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * (c) Netresearch DTT GmbH <info@netresearch.de>
 */

namespace Netresearch\ExtensionScannerCli\Output;

use Netresearch\ExtensionScannerCli\Dto\ScanMatch;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface for Extension Scanner output formatters.
 *
 * Implement this interface to create custom output formats for scan results.
 */
interface OutputFormatterInterface
{
    /**
     * Format and output scan results.
     *
     * @param OutputInterface                      $output      The console output to write to
     * @param array<string, array<int, ScanMatch>> $allMatches  Matches grouped by extension key
     * @param int                                  $totalStrong Total number of strong (definite) matches
     * @param int                                  $totalWeak   Total number of weak (potential) matches
     */
    public function format(
        OutputInterface $output,
        array $allMatches,
        int $totalStrong,
        int $totalWeak,
    ): void;
}
