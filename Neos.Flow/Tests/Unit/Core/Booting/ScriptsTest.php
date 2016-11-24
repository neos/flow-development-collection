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
 * Testcase for the initialization scripts
 */
class ScriptsTest extends UnitTestCase
{
    /**
     * @var Scripts|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scriptsMock;

    /**
     */
    protected function setUp()
    {
        parent::setUp();
        $this->scriptsMock = $this->getAccessibleMock(Scripts::class, ['dummy']);
    }

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
        $actual = $this->scriptsMock->_call('buildSubprocessCommand', 'flow:foo:identifier', $settings);
        $this->assertContains(sprintf(' -c %s ', escapeshellarg(php_ini_loaded_file())), $actual, $message);

        $settings['core']['subRequestPhpIniPathAndFilename'] = null;
        $message = 'The command must contain the current ini because it is explicitly set, but NULL, in settings.';
        $actual = $this->scriptsMock->_call('buildSubprocessCommand', 'flow:foo:identifier', $settings);
        $this->assertContains(sprintf(' -c %s ', escapeshellarg(php_ini_loaded_file())), $actual, $message);

        $settings['core']['subRequestPhpIniPathAndFilename'] = '/foo/ini/path';
        $message = 'The command must contain a specified ini file path because it is set in settings.';
        $actual = $this->scriptsMock->_call('buildSubprocessCommand', 'flow:foo:identifier', $settings);
        $this->assertContains(sprintf(' -c %s ', escapeshellarg('/foo/ini/path')), $actual, $message);

        $settings['core']['subRequestPhpIniPathAndFilename'] = false;
        $message = 'The command must not contain an ini file path because it is set to FALSE in settings.';
        $actual = $this->scriptsMock->_call('buildSubprocessCommand', 'flow:foo:identifier', $settings);
        $this->assertNotContains(' -c ', $actual, $message);
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
        $actual = $this->scriptsMock->_call('buildSubprocessCommand', 'flow:foo:identifier', $settings);

        $this->assertContains(sprintf(' -d %s=%s ', escapeshellarg('someSetting'), escapeshellarg('withValue')), $actual);
        $this->assertContains(sprintf(' -d %s ', escapeshellarg('someFlagSettingWithoutValue')), $actual);
    }
}
