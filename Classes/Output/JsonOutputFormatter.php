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
 * JSON output formatter for machine-readable output.
 *
 * Produces structured JSON output suitable for processing by
 * other tools or for storage and later analysis.
 */
class JsonOutputFormatter implements OutputFormatterInterface
{
    public function format(
        OutputInterface $output,
        array $allMatches,
        int $totalStrong,
        int $totalWeak,
    ): void {
        $result = [
            'summary' => [
                'total' => $totalStrong + $totalWeak,
                'strong' => $totalStrong,
                'weak' => $totalWeak,
                'extensions_scanned' => \count($allMatches),
                'timestamp' => date('c'),
            ],
            'extensions' => [],
        ];

        foreach ($allMatches as $extensionKey => $matches) {
            $extensionResult = [
                'key' => $extensionKey,
                'total' => \count($matches),
                'strong' => 0,
                'weak' => 0,
                'matches' => [],
            ];

            /** @var ScanMatch $match */
            foreach ($matches as $match) {
                if ($match->isStrong()) {
                    ++$extensionResult['strong'];
                } else {
                    ++$extensionResult['weak'];
                }

                $extensionResult['matches'][] = $match->toArray();
            }

            $result['extensions'][] = $extensionResult;
        }

        $json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $output->writeln($json);
    }
}
