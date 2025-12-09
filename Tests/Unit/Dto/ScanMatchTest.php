<?php

declare(strict_types=1);

namespace Netresearch\ExtensionScannerCli\Tests\Unit\Dto;

use Netresearch\ExtensionScannerCli\Dto\ScanMatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScanMatch::class)]
final class ScanMatchTest extends TestCase
{
    #[Test]
    public function constructorSetsAllProperties(): void
    {
        $match = new ScanMatch(
            'Classes/Test.php',
            '/var/www/ext/Classes/Test.php',
            42,
            'strong',
            'Deprecated method call',
            'TYPO3\\CMS\\Install\\Php\\Matcher\\MethodCallMatcher',
            ['Deprecation-12345.rst'],
        );

        self::assertSame('Classes/Test.php', $match->file);
        self::assertSame('/var/www/ext/Classes/Test.php', $match->absolutePath);
        self::assertSame(42, $match->line);
        self::assertSame('strong', $match->indicator);
        self::assertSame('Deprecated method call', $match->message);
        self::assertSame('TYPO3\\CMS\\Install\\Php\\Matcher\\MethodCallMatcher', $match->matcherClass);
        self::assertSame(['Deprecation-12345.rst'], $match->restFiles);
    }

    #[Test]
    public function isStrongReturnsTrueForStrongIndicator(): void
    {
        $match = new ScanMatch('test.php', '/test.php', 1, 'strong', 'msg', 'Matcher');

        self::assertTrue($match->isStrong());
        self::assertFalse($match->isWeak());
    }

    #[Test]
    public function isWeakReturnsTrueForWeakIndicator(): void
    {
        $match = new ScanMatch('test.php', '/test.php', 1, 'weak', 'msg', 'Matcher');

        self::assertTrue($match->isWeak());
        self::assertFalse($match->isStrong());
    }

    #[Test]
    public function getMatcherNameExtractsClassName(): void
    {
        $match = new ScanMatch(
            'test.php',
            '/test.php',
            1,
            'strong',
            'msg',
            'TYPO3\\CMS\\Install\\ExtensionScanner\\Php\\Matcher\\MethodCallMatcher',
        );

        self::assertSame('MethodCallMatcher', $match->getMatcherName());
    }

    #[Test]
    public function getMatcherNameReturnsOriginalForSimpleName(): void
    {
        $match = new ScanMatch('test.php', '/test.php', 1, 'strong', 'msg', 'SimpleMatcher');

        self::assertSame('SimpleMatcher', $match->getMatcherName());
    }

    #[Test]
    public function getMatchTypeFormatsReadableType(): void
    {
        $match = new ScanMatch(
            'test.php',
            '/test.php',
            1,
            'strong',
            'msg',
            'TYPO3\\CMS\\Install\\ExtensionScanner\\Php\\Matcher\\MethodCallStaticMatcher',
        );

        self::assertSame('Method Call Static', $match->getMatchType());
    }

    #[Test]
    public function fromMatcherOutputCreatesValidInstance(): void
    {
        $rawMatch = [
            'line' => 100,
            'indicator' => 'weak',
            'message' => 'Deprecated API',
            'restFiles' => ['Breaking-67890.rst'],
        ];

        $match = ScanMatch::fromMatcherOutput(
            $rawMatch,
            'Classes/Service.php',
            '/var/www/ext/Classes/Service.php',
            'TestMatcher',
        );

        self::assertSame('Classes/Service.php', $match->file);
        self::assertSame('/var/www/ext/Classes/Service.php', $match->absolutePath);
        self::assertSame(100, $match->line);
        self::assertSame('weak', $match->indicator);
        self::assertSame('Deprecated API', $match->message);
        self::assertSame('TestMatcher', $match->matcherClass);
        self::assertSame(['Breaking-67890.rst'], $match->restFiles);
    }

    #[Test]
    public function fromMatcherOutputHandlesMissingFields(): void
    {
        $rawMatch = [];

        $match = ScanMatch::fromMatcherOutput(
            $rawMatch,
            'test.php',
            '/test.php',
            'Matcher',
        );

        self::assertSame(0, $match->line);
        self::assertSame('strong', $match->indicator);
        self::assertSame('Unknown issue', $match->message);
        self::assertSame([], $match->restFiles);
    }

    #[Test]
    public function fromMatcherOutputFiltersInvalidRestFiles(): void
    {
        $rawMatch = [
            'restFiles' => ['Valid.rst', 123, null, 'Another.rst', ['nested']],
        ];

        $match = ScanMatch::fromMatcherOutput($rawMatch, 'test.php', '/test.php', 'Matcher');

        self::assertSame(['Valid.rst', 'Another.rst'], $match->restFiles);
    }

    #[Test]
    public function toArrayReturnsCorrectStructure(): void
    {
        $match = new ScanMatch(
            'Classes/Test.php',
            '/var/www/ext/Classes/Test.php',
            42,
            'strong',
            'Test message',
            'TYPO3\\CMS\\Install\\Php\\Matcher\\MethodCallMatcher',
            ['Deprecation-12345.rst'],
        );

        $array = $match->toArray();

        self::assertSame('Classes/Test.php', $array['file']);
        self::assertSame('/var/www/ext/Classes/Test.php', $array['absolutePath']);
        self::assertSame(42, $array['line']);
        self::assertSame('strong', $array['indicator']);
        self::assertSame('Test message', $array['message']);
        self::assertSame('MethodCallMatcher', $array['matcherClass']);
        self::assertSame(['Deprecation-12345.rst'], $array['restFiles']);
    }

    #[Test]
    public function restFilesDefaultsToEmptyArray(): void
    {
        $match = new ScanMatch('test.php', '/test.php', 1, 'strong', 'msg', 'Matcher');

        self::assertSame([], $match->restFiles);
    }
}
