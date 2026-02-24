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

namespace Netresearch\ExtensionScannerCli\Dto;

/**
 * Data Transfer Object representing a single scanner match result.
 *
 * This DTO encapsulates all information about a deprecation/removal
 * finding detected by the Extension Scanner matchers.
 */
final readonly class ScanMatch
{
    /**
     * @param string        $file         Relative file path from extension root
     * @param string        $absolutePath Absolute file path on filesystem
     * @param int           $line         Line number where the match was found
     * @param string        $indicator    Match strength: 'strong' (definite) or 'weak' (potential)
     * @param string        $message      Description of the deprecated/removed API usage
     * @param string        $matcherClass Fully qualified class name of the matcher that found this
     * @param array<string> $restFiles    List of related RST documentation files
     */
    public function __construct(
        public string $file,
        public string $absolutePath,
        public int $line,
        public string $indicator,
        public string $message,
        public string $matcherClass,
        public array $restFiles = [],
    ) {
    }

    /**
     * Check if this is a strong (definite) match.
     */
    public function isStrong(): bool
    {
        return $this->indicator === 'strong';
    }

    /**
     * Check if this is a weak (potential) match.
     */
    public function isWeak(): bool
    {
        return $this->indicator === 'weak';
    }

    /**
     * Get the short matcher class name without namespace.
     */
    public function getMatcherName(): string
    {
        $parts = explode('\\', $this->matcherClass);

        return end($parts) ?: $this->matcherClass;
    }

    /**
     * Get a human-readable match type derived from the matcher class.
     */
    public function getMatchType(): string
    {
        $name = $this->getMatcherName();
        $type = str_replace('Matcher', '', $name);

        return preg_replace('/([a-z])([A-Z])/', '$1 $2', $type) ?? $type;
    }

    /**
     * Create a ScanMatch from raw matcher output array.
     *
     * @param array<string, mixed> $rawMatch     Raw match data from TYPO3 core matcher
     * @param string               $relativeFile Relative file path
     * @param string               $absolutePath Absolute file path
     * @param string               $matcherClass Matcher class that produced this match
     */
    public static function fromMatcherOutput(
        array $rawMatch,
        string $relativeFile,
        string $absolutePath,
        string $matcherClass,
    ): self {
        $restFiles = $rawMatch['restFiles'] ?? [];

        return new self(
            file: $relativeFile,
            absolutePath: $absolutePath,
            line: isset($rawMatch['line']) && is_numeric($rawMatch['line']) ? (int) $rawMatch['line'] : 0,
            indicator: isset($rawMatch['indicator']) && \is_string($rawMatch['indicator']) ? $rawMatch['indicator'] : 'strong',
            message: isset($rawMatch['message']) && \is_string($rawMatch['message']) ? $rawMatch['message'] : 'Unknown issue',
            matcherClass: $matcherClass,
            restFiles: \is_array($restFiles) ? array_values(array_filter($restFiles, '\is_string')) : [],
        );
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'absolutePath' => $this->absolutePath,
            'line' => $this->line,
            'indicator' => $this->indicator,
            'message' => $this->message,
            'matcherClass' => $this->getMatcherName(),
            'restFiles' => $this->restFiles,
        ];
    }
}
