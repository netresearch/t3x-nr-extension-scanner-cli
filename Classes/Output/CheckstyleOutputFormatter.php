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
        $matchesByFile = [];
        foreach ($allMatches as $extensionKey => $matches) {
            foreach ($matches as $match) {
                $absolutePath = $match['absolutePath'] ?? $match['file'] ?? 'unknown';
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
                $xml->writeAttribute('line', (string)($match['line'] ?? 0));
                $xml->writeAttribute('column', '0');
                $xml->writeAttribute(
                    'severity',
                    ($match['indicator'] ?? 'strong') === 'strong' ? 'error' : 'warning',
                );
                $xml->writeAttribute('message', $match['message'] ?? 'Unknown issue');
                $xml->writeAttribute('source', $this->formatSource($match['matcherClass'] ?? ''));
                $xml->endElement(); // error
            }

            $xml->endElement(); // file
        }

        $xml->endElement(); // checkstyle
        $xml->endDocument();

        $output->write($xml->outputMemory());
    }

    /**
     * Format the source identifier from the matcher class.
     */
    private function formatSource(string $matcherClass): string
    {
        $parts = explode('\\', $matcherClass);
        $className = end($parts) ?: 'Unknown';
        return 'TYPO3.ExtensionScanner.' . str_replace('Matcher', '', $className);
    }
}
