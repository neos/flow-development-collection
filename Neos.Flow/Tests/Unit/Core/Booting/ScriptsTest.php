<?php
namespace Neos\Flow\Tests\Unit\Core\Booting;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Core\Booting\Scripts;
use Neos\Flow\Tests\UnitTestCase;

/**
 * This is something that PHPUnit would have to do in order to support stubbing static methods. And
 * it would only work if those static methods are called with `static::`, otherwise it breaks badly
 * without a way to work around it. And that's the reason why PHPUnit doesn't support mocking static
 * classes since ages any more and why you shouldn't use static methods for anything but trivial
 * methods that do not do any IO. Unfortunately, we do that in the Scripts.
 * TODO: Refactor Scripts class to be more testable.
 */
class ScriptsMock extends Scripts
{
    protected static function ensureCLISubrequestsUseCurrentlyRunningPhpBinary($phpBinaryPathAndFilename)
    {
    }

    protected static function ensureWebSubrequestsUseCurrentlyRunningPhpVersion($phpCommand)
    {
    }

    public static function buildSubprocessCommand(string $commandIdentifier, array $settings, array $commandArguments = []): string
    {
        return parent::buildSubprocessCommand($commandIdentifier, $settings, $commandArguments);
    }
}

/**
 * Testcase for the initialization scripts
 */
class ScriptsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function subProcessCommandEvaluatesIniFileUsageSettingCorrectly()
    {
        $settings = ['core' => [
            'context' => 'Testing',
            'phpBinaryPathAndFilename' => '/foo/var/php'
        ]];

        $message = 'The command must contain the current ini because it is not explicitly set in settings.';
        $actual = ScriptsMock::buildSubprocessCommand('flow:foo:identifier', $settings);
        self::assertStringContainsString(sprintf(' -c %s ', escapeshellarg(php_ini_loaded_file())), $actual, $message);

        $settings['core']['subRequestPhpIniPathAndFilename'] = null;
        $message = 'The command must contain the current ini because it is explicitly set, but NULL, in settings.';
        $actual = ScriptsMock::buildSubprocessCommand('flow:foo:identifier', $settings);
        self::assertStringContainsString(sprintf(' -c %s ', escapeshellarg(php_ini_loaded_file())), $actual, $message);

        $settings['core']['subRequestPhpIniPathAndFilename'] = '/foo/ini/path';
        $message = 'The command must contain a specified ini file path because it is set in settings.';
        $actual = ScriptsMock::buildSubprocessCommand('flow:foo:identifier', $settings);
        self::assertStringContainsString(sprintf(' -c %s ', escapeshellarg('/foo/ini/path')), $actual, $message);

        $settings['core']['subRequestPhpIniPathAndFilename'] = false;
        $message = 'The command must not contain an ini file path because it is set to FALSE in settings.';
        $actual = ScriptsMock::buildSubprocessCommand('flow:foo:identifier', $settings);
        self::assertStringNotContainsString(' -c ', $actual, $message);
    }

    /**
     * @test
     */
    public function subProcessCommandEvaluatesSubRequestIniEntriesCorrectly()
    {
        $settings = ['core' => [
            'context' => 'Testing',
            'phpBinaryPathAndFilename' => '/must/be/set/according/to/schema',
            'subRequestIniEntries' => ['someSetting' => 'withValue', 'someFlagSettingWithoutValue' => '']
        ]];
        $actual = ScriptsMock::buildSubprocessCommand('flow:foo:identifier', $settings);

        self::assertStringContainsString(sprintf(' -d %s=%s ', escapeshellarg('someSetting'), escapeshellarg('withValue')), $actual);
        self::assertStringContainsString(sprintf(' -d %s ', escapeshellarg('someFlagSettingWithoutValue')), $actual);
    }
}
