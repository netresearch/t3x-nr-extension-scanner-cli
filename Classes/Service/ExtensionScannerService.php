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

namespace Netresearch\ExtensionScannerCli\Service;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\ExtensionScanner\Php\CodeStatistics;
use TYPO3\CMS\Install\ExtensionScanner\Php\GeneratorClassesResolver;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\AbstractCoreMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ArrayDimensionMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ArrayGlobalMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ClassConstantMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ClassNameMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ConstantMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\ConstructorArgumentMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\FunctionCallMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\InterfaceMethodChangedMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodAnnotationMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentDroppedMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentDroppedStaticMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentRequiredMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentRequiredStaticMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodArgumentUnusedMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodCallMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\MethodCallStaticMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\PropertyAnnotationMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\PropertyExistsStaticMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\PropertyProtectedMatcher;
use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\PropertyPublicMatcher;

/**
 * Service for scanning PHP files for deprecated/removed TYPO3 API usage.
 *
 * This service provides the core scanning functionality used by the
 * extension:scan CLI command. It reuses the existing TYPO3 Core
 * Extension Scanner matchers from EXT:install.
 *
 * Note: This service uses @internal TYPO3 Core classes. While these classes
 * are stable in practice, they are not part of the public API and may change
 * between TYPO3 versions. This is acceptable for a development/CI tool.
 */
class ExtensionScannerService
{
    /**
     * Mapping of matcher classes to their configuration files.
     *
     * @var array<class-string<AbstractCoreMatcher>, string>
     */
    private const MATCHER_CONFIGURATIONS = [
        ArrayDimensionMatcher::class => 'ArrayDimensionMatcher.php',
        ArrayGlobalMatcher::class => 'ArrayGlobalMatcher.php',
        ClassConstantMatcher::class => 'ClassConstantMatcher.php',
        ClassNameMatcher::class => 'ClassNameMatcher.php',
        ConstantMatcher::class => 'ConstantMatcher.php',
        ConstructorArgumentMatcher::class => 'ConstructorArgumentMatcher.php',
        FunctionCallMatcher::class => 'FunctionCallMatcher.php',
        InterfaceMethodChangedMatcher::class => 'InterfaceMethodChangedMatcher.php',
        MethodAnnotationMatcher::class => 'MethodAnnotationMatcher.php',
        MethodArgumentDroppedMatcher::class => 'MethodArgumentDroppedMatcher.php',
        MethodArgumentDroppedStaticMatcher::class => 'MethodArgumentDroppedStaticMatcher.php',
        MethodArgumentRequiredMatcher::class => 'MethodArgumentRequiredMatcher.php',
        MethodArgumentRequiredStaticMatcher::class => 'MethodArgumentRequiredStaticMatcher.php',
        MethodArgumentUnusedMatcher::class => 'MethodArgumentUnusedMatcher.php',
        MethodCallMatcher::class => 'MethodCallMatcher.php',
        MethodCallStaticMatcher::class => 'MethodCallStaticMatcher.php',
        PropertyAnnotationMatcher::class => 'PropertyAnnotationMatcher.php',
        PropertyExistsStaticMatcher::class => 'PropertyExistsStaticMatcher.php',
        PropertyProtectedMatcher::class => 'PropertyProtectedMatcher.php',
        PropertyPublicMatcher::class => 'PropertyPublicMatcher.php',
    ];

    /**
     * @var array<class-string<AbstractCoreMatcher>, array<string, mixed>>|null
     */
    private ?array $matcherConfigurations = null;

    private ?Parser $parser = null;

    /**
     * Scan a directory path for deprecated API usage.
     *
     * @param string $path Directory path to scan
     * @param callable|null $progressCallback Optional callback for progress updates: fn(int $current, int $total)
     * @param callable|null $parseErrorCallback Optional callback for parse errors: fn(string $file, string $error)
     * @return array<int, array<string, mixed>> Array of matches
     */
    public function scanPath(
        string $path,
        ?callable $progressCallback = null,
        ?callable $parseErrorCallback = null
    ): array {
        $matches = [];

        $finder = new Finder();
        $finder->files()
            ->in($path)
            ->name('*.php')
            ->notPath('vendor')
            ->notPath('node_modules')
            ->notPath('.Build');

        $files = iterator_to_array($finder);
        $fileCount = count($files);

        if ($fileCount === 0) {
            return $matches;
        }

        $matcherConfigurations = $this->getMatcherConfigurations();
        $parser = $this->getParser();

        $processedFiles = 0;
        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $fileMatches = $this->scanFile($file, $parser, $matcherConfigurations, $parseErrorCallback);
            $matches = array_merge($matches, $fileMatches);

            $processedFiles++;
            if ($progressCallback !== null) {
                $progressCallback($processedFiles, $fileCount);
            }
        }

        return $matches;
    }

    /**
     * Scan a single PHP file.
     *
     * @param SplFileInfo $file The file to scan
     * @param Parser|null $parser Optional parser instance (for performance when scanning multiple files)
     * @param array<class-string<AbstractCoreMatcher>, array<string, mixed>>|null $matcherConfigurations Optional matcher configs
     * @param callable|null $parseErrorCallback Optional callback for parse errors
     * @return array<int, array<string, mixed>>
     */
    public function scanFile(
        SplFileInfo $file,
        ?Parser $parser = null,
        ?array $matcherConfigurations = null,
        ?callable $parseErrorCallback = null
    ): array {
        $matches = [];
        $parser = $parser ?? $this->getParser();
        $matcherConfigurations = $matcherConfigurations ?? $this->getMatcherConfigurations();

        $fileContent = $file->getContents();

        try {
            $statements = $parser->parse($fileContent);
        } catch (\PhpParser\Error $e) {
            if ($parseErrorCallback !== null) {
                $parseErrorCallback($file->getRelativePathname(), $e->getMessage());
            }
            return $matches;
        }

        if ($statements === null) {
            return $matches;
        }

        // First pass: resolve names and check if file is ignored
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor(new GeneratorClassesResolver());

        $codeStatistics = new CodeStatistics();
        $traverser->addVisitor($codeStatistics);

        $statements = $traverser->traverse($statements);

        if ($codeStatistics->isFileIgnored()) {
            return $matches;
        }

        // Second pass: run all matchers in a single traversal for better performance
        $matcherTraverser = new NodeTraverser();
        /** @var array<class-string<AbstractCoreMatcher>, AbstractCoreMatcher> $matchers */
        $matchers = [];
        foreach ($matcherConfigurations as $matcherClass => $configuration) {
            /** @var AbstractCoreMatcher $matcher */
            $matcher = new $matcherClass($configuration);
            $matchers[$matcherClass] = $matcher;
            $matcherTraverser->addVisitor($matcher);
        }

        $matcherTraverser->traverse($statements);

        // Collect matches from all matchers
        foreach ($matchers as $matcherClass => $matcher) {
            foreach ($matcher->getMatches() as $match) {
                $match['file'] = $file->getRelativePathname();
                $match['absolutePath'] = $file->getRealPath();
                $match['matcherClass'] = $matcherClass;
                $matches[] = $match;
            }
        }

        return $matches;
    }

    /**
     * Calculate match statistics from results.
     *
     * @param array<int, array<string, mixed>> $matches
     * @return array{total: int, strong: int, weak: int}
     */
    public function calculateStatistics(array $matches): array
    {
        $strong = 0;
        $weak = 0;

        foreach ($matches as $match) {
            if (($match['indicator'] ?? 'strong') === 'strong') {
                $strong++;
            } else {
                $weak++;
            }
        }

        return [
            'total' => $strong + $weak,
            'strong' => $strong,
            'weak' => $weak,
        ];
    }

    /**
     * Get a list of available matcher classes.
     *
     * @return array<class-string<AbstractCoreMatcher>>
     */
    public function getAvailableMatcherClasses(): array
    {
        return array_keys(self::MATCHER_CONFIGURATIONS);
    }

    /**
     * Load all matcher configurations from the Configuration/ExtensionScanner/Php directory.
     *
     * @return array<class-string<AbstractCoreMatcher>, array<string, mixed>>
     */
    public function getMatcherConfigurations(): array
    {
        if ($this->matcherConfigurations !== null) {
            return $this->matcherConfigurations;
        }

        $configurations = [];
        $basePath = GeneralUtility::getFileAbsFileName(
            'EXT:install/Configuration/ExtensionScanner/Php/'
        );

        foreach (self::MATCHER_CONFIGURATIONS as $matcherClass => $configFile) {
            $configPath = $basePath . $configFile;
            if (file_exists($configPath)) {
                $configurations[$matcherClass] = require $configPath;
            }
        }

        $this->matcherConfigurations = $configurations;
        return $configurations;
    }

    /**
     * Get the PHP parser instance.
     */
    private function getParser(): Parser
    {
        if ($this->parser === null) {
            $this->parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));
        }
        return $this->parser;
    }
}
