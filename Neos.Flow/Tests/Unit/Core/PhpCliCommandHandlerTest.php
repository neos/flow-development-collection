<?php
namespace Neos\Flow\Tests\Unit\Core;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Core\PhpCliCommandHandler;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Tests for the PppCliCommandHandler
 */
class PhpCliCommandHandlerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function subProcessCommandEvaluatesIniFileUsageSettingCorrectly()
    {
        $settings = [
            'core' => [
                'context' => 'Testing',
                'phpBinaryPathAndFilename' => '/foo/var/php'
            ]
        ];

        $message = 'The command must contain the current ini because it is not explicitly set in settings.';
        $actual = PhpCliCommandHandler::buildPhpCommand($settings);
        self::assertStringContainsString(sprintf('-c %s', escapeshellarg(php_ini_loaded_file())), $actual, $message);

        $settings['core']['subRequestPhpIniPathAndFilename'] = null;
        $message = 'The command must contain the current ini because it is explicitly set, but NULL, in settings.';
        $actual = PhpCliCommandHandler::buildPhpCommand($settings);
        self::assertStringContainsString(sprintf('-c %s', escapeshellarg(php_ini_loaded_file())), $actual, $message);

        $settings['core']['subRequestPhpIniPathAndFilename'] = '/foo/ini/path';
        $message = 'The command must contain a specified ini file path because it is set in settings.';
        $actual = PhpCliCommandHandler::buildPhpCommand($settings);
        self::assertStringContainsString(sprintf('-c %s', escapeshellarg('/foo/ini/path')), $actual, $message);

        $settings['core']['subRequestPhpIniPathAndFilename'] = false;
        $message = 'The command must not contain an ini file path because it is set to FALSE in settings.';
        $actual = PhpCliCommandHandler::buildPhpCommand($settings);
        self::assertStringNotContainsString(' -c', $actual, $message);
    }

    /**
     * @test
     */
    public function subProcessCommandEvaluatesSubRequestIniEntriesCorrectly(): void
    {
        PhpCliCommandHandler::setBuildPhpCommand('/must/be/set/according/to/schema ');
        $settings = [
            'core' => [
                'context' => 'Testing',
                'phpBinaryPathAndFilename' => '/must/be/set/according/to/schema',
                'subRequestIniEntries' => ['someSetting' => 'withValue', 'someFlagSettingWithoutValue' => '']
            ]
        ];
        $actual = PhpCliCommandHandler::buildSubprocessCommand('some:command', $settings);

        self::assertStringContainsString(sprintf(' -d %s=%s', escapeshellarg('someSetting'), escapeshellarg('withValue')), $actual);
        self::assertStringContainsString(sprintf(' -d %s', escapeshellarg('someFlagSettingWithoutValue')), $actual);
    }
}
