<?php

declare(strict_types=1);

/*
 * This file is part of the "nr_extension_scanner_cli" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * (c) Netresearch DTT GmbH
 *
 * SPDX-License-Identifier: MIT
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

namespace Netresearch\ExtensionScannerCli\Output;

use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * Prepares text from scanned extensions (file names, parser messages) for output.
 *
 * File names and parser messages come from the code being scanned. Control
 * characters in them would reach the terminal as escape sequences, and console
 * tags such as <info> would be read as formatting.
 */
final class ConsoleText
{
    private const REPLACEMENT = '?';

    /**
     * Replaces ASCII control characters, DEL and the UTF-8 encoded C1 control
     * characters (U+0080 to U+009F) with "?".
     */
    public static function withoutControlCharacters(string $text): string
    {
        return preg_replace('/[\x00-\x1F\x7F]|\xC2[\x80-\x9F]/', self::REPLACEMENT, $text) ?? $text;
    }

    /**
     * Text for output that applies console formatting: without control
     * characters and with console tags escaped, so it is shown literally.
     */
    public static function forFormattedOutput(string $text): string
    {
        return OutputFormatter::escape(self::withoutControlCharacters($text));
    }
}
