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

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Table output formatter for human-readable console output.
 *
 * Displays scan results in a formatted table with color-coded
 * indicators for strong/weak matches and detailed information.
 */
class TableOutputFormatter implements OutputFormatterInterface
{
    public function __construct(
        private readonly SymfonyStyle $io,
    ) {}

    public function format(
        OutputInterface $output,
        array $allMatches,
        int $totalStrong,
        int $totalWeak,
    ): void {
        $totalMatches = $totalStrong + $totalWeak;

        if ($totalMatches === 0) {
            $this->io->success('No deprecated API usage found!');
            return;
        }

        foreach ($allMatches as $extensionKey => $matches) {
            if (empty($matches)) {
                continue;
            }

            $this->io->title(sprintf('Results for: %s', $extensionKey));

            $table = new Table($output);
            $table->setHeaders(['File', 'Line', 'Type', 'Message', 'Indicator']);

            foreach ($matches as $match) {
                $indicator = ($match['indicator'] ?? 'strong') === 'strong'
                    ? '<error> STRONG </error>'
                    : '<comment> WEAK </comment>';

                $message = $match['message'] ?? 'Unknown';
                // Truncate long messages for table display
                if (strlen($message) > 60) {
                    $message = substr($message, 0, 57) . '...';
                }

                $table->addRow([
                    $match['file'] ?? 'Unknown',
                    $match['line'] ?? '?',
                    $this->formatMatchType($match['matcherClass'] ?? ''),
                    $message,
                    $indicator,
                ]);
            }

            $table->render();
            $this->io->newLine();
        }

        // Summary
        $this->io->section('Summary');

        if ($totalStrong > 0) {
            $this->io->error(sprintf(
                'Found %d strong match(es) that WILL break on upgrade.',
                $totalStrong,
            ));
        }

        if ($totalWeak > 0) {
            $this->io->warning(sprintf(
                'Found %d weak match(es) that MAY need attention.',
                $totalWeak,
            ));
        }

        $this->io->text([
            '',
            sprintf('Total issues: %d (%d strong, %d weak)', $totalMatches, $totalStrong, $totalWeak),
            '',
            '<info>Strong matches</info>: Definite usage of removed/deprecated API - must be fixed.',
            '<comment>Weak matches</comment>: Potential matches that need manual verification.',
        ]);
    }

    /**
     * Format the matcher class name into a readable type.
     */
    private function formatMatchType(string $matcherClass): string
    {
        // Extract the class name without namespace
        $parts = explode('\\', $matcherClass);
        $className = end($parts);

        // Remove "Matcher" suffix and convert to readable format
        $type = str_replace('Matcher', '', $className);

        // Convert CamelCase to readable format
        return preg_replace('/([a-z])([A-Z])/', '$1 $2', $type) ?? $type;
    }
}
