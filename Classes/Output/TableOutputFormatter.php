<?php

declare(strict_types=1);

/*
 * This file is part of the "nr_extension_scanner_cli" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * (c) Netresearch DTT GmbH
 */

namespace Netresearch\ExtensionScannerCli\Output;

use Netresearch\ExtensionScannerCli\Dto\ScanMatch;
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
    ) {
    }

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

            $this->io->title(\sprintf('Results for: %s', (string) $extensionKey));

            $table = new Table($output);
            $table->setHeaders(['File', 'Line', 'Type', 'Message', 'Indicator']);

            /** @var ScanMatch $match */
            foreach ($matches as $match) {
                $indicator = $match->isStrong()
                    ? '<error> STRONG </error>'
                    : '<comment> WEAK </comment>';

                // Truncate long messages for table display
                $message = $match->message;
                if (\strlen($message) > 60) {
                    $message = substr($message, 0, 57) . '...';
                }

                $table->addRow([
                    $match->file,
                    (string) $match->line,
                    $match->getMatchType(),
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
            $this->io->error(\sprintf(
                'Found %d strong match(es) that WILL break on upgrade.',
                $totalStrong,
            ));
        }

        if ($totalWeak > 0) {
            $this->io->warning(\sprintf(
                'Found %d weak match(es) that MAY need attention.',
                $totalWeak,
            ));
        }

        $this->io->text([
            '',
            \sprintf('Total issues: %d (%d strong, %d weak)', $totalMatches, $totalStrong, $totalWeak),
            '',
            '<info>Strong matches</info>: Definite usage of removed/deprecated API - must be fixed.',
            '<comment>Weak matches</comment>: Potential matches that need manual verification.',
        ]);
    }
}
