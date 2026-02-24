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
use Symfony\Component\Console\Output\OutputInterface;
use XMLWriter;

/**
 * Checkstyle XML output formatter for CI/CD integration.
 *
 * Produces output in the Checkstyle XML format, which is widely
 * supported by CI tools like Jenkins, GitLab CI, GitHub Actions,
 * and various IDE plugins.
 */
class CheckstyleOutputFormatter implements OutputFormatterInterface
{
    public function format(
        OutputInterface $output,
        array $allMatches,
        int $totalStrong,
        int $totalWeak,
    ): void {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('  ');
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('checkstyle');
        $xml->writeAttribute('version', '4.3');

        // Group all matches by absolute file path for checkstyle format
        /** @var array<string, list<ScanMatch>> $matchesByFile */
        $matchesByFile = [];
        foreach ($allMatches as $matches) {
            foreach ($matches as $match) {
                $absolutePath = $match->absolutePath;
                if (!isset($matchesByFile[$absolutePath])) {
                    $matchesByFile[$absolutePath] = [];
                }
                $matchesByFile[$absolutePath][] = $match;
            }
        }

        foreach ($matchesByFile as $filePath => $matches) {
            $xml->startElement('file');
            $xml->writeAttribute('name', $filePath);

            foreach ($matches as $match) {
                $xml->startElement('error');
                $xml->writeAttribute('line', (string) $match->line);
                $xml->writeAttribute('column', '0');
                $xml->writeAttribute('severity', $match->isStrong() ? 'error' : 'warning');
                $xml->writeAttribute('message', $match->message);
                $xml->writeAttribute('source', 'TYPO3.ExtensionScanner.' . $match->getMatchType());
                $xml->endElement(); // error
            }

            $xml->endElement(); // file
        }

        $xml->endElement(); // checkstyle
        $xml->endDocument();

        $output->write($xml->outputMemory());
    }
}
