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

namespace Netresearch\ExtensionScannerCli\Output;

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
        int $totalWeak
    ): void {
        $result = [
            'summary' => [
                'total' => $totalStrong + $totalWeak,
                'strong' => $totalStrong,
                'weak' => $totalWeak,
                'extensions_scanned' => count($allMatches),
                'timestamp' => date('c'),
            ],
            'extensions' => [],
        ];

        foreach ($allMatches as $extensionKey => $matches) {
            $extensionResult = [
                'key' => $extensionKey,
                'total' => count($matches),
                'strong' => 0,
                'weak' => 0,
                'matches' => [],
            ];

            foreach ($matches as $match) {
                $indicator = $match['indicator'] ?? 'strong';
                if ($indicator === 'strong') {
                    $extensionResult['strong']++;
                } else {
                    $extensionResult['weak']++;
                }

                $extensionResult['matches'][] = [
                    'file' => $match['file'] ?? null,
                    'absolutePath' => $match['absolutePath'] ?? null,
                    'line' => $match['line'] ?? null,
                    'indicator' => $indicator,
                    'message' => $match['message'] ?? null,
                    'restFiles' => $match['restFiles'] ?? [],
                    'matcherClass' => $this->formatMatcherClass($match['matcherClass'] ?? ''),
                ];
            }

            $result['extensions'][] = $extensionResult;
        }

        $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Format the matcher class name for cleaner JSON output.
     */
    private function formatMatcherClass(string $matcherClass): string
    {
        $parts = explode('\\', $matcherClass);
        return end($parts) ?: $matcherClass;
    }
}
