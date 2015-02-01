<?php
namespace TYPO3\Flow\Tests\Unit\Core\Booting;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the initialization scripts
 *
 */
class ScriptsTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Core\Booting\Scripts|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $scriptsMock;

	/**
	 */
	protected function setUp() {
		parent::setUp();
		$this->scriptsMock = $this->getAccessibleMock('TYPO3\Flow\Core\Booting\Scripts', array('dummy'));
	}

	/**
	 * @test
	 */
	public function subProcessCommandEvaluatesIniFileUsageSettingCorrectly() {
		$settings = array('core' => array(
			'context' => 'Testing',
			'phpBinaryPathAndFilename' => '/foo/var/php'
		));

		$message = 'The command must contain the current ini because it is not explicitly set in settings.';
		$actual = $this->scriptsMock->_call('buildSubprocessCommand', 'flow:foo:identifier', $settings);
		$this->assertContains(sprintf(' -c %s ', escapeshellarg(php_ini_loaded_file())), $actual, $message);

		$settings['core']['subRequestPhpIniPathAndFilename'] = NULL;
		$message = 'The command must contain the current ini because it is explicitly set, but NULL, in settings.';
		$actual = $this->scriptsMock->_call('buildSubprocessCommand', 'flow:foo:identifier', $settings);
		$this->assertContains(sprintf(' -c %s ', escapeshellarg(php_ini_loaded_file())), $actual, $message);

		$settings['core']['subRequestPhpIniPathAndFilename'] = '/foo/ini/path';
		$message = 'The command must contain a specified ini file path because it is set in settings.';
		$actual = $this->scriptsMock->_call('buildSubprocessCommand', 'flow:foo:identifier', $settings);
		$this->assertContains(sprintf(' -c %s ', escapeshellarg('/foo/ini/path')), $actual, $message);

		$settings['core']['subRequestPhpIniPathAndFilename'] = FALSE;
		$message = 'The command must not contain an ini file path because it is set to FALSE in settings.';
		$actual = $this->scriptsMock->_call('buildSubprocessCommand', 'flow:foo:identifier', $settings);
		$this->assertNotContains(' -c ', $actual, $message);
	}

	/**
	 * @test
	 */
	public function subProcessCommandEvaluatesSubRequestIniEntriesCorrectly() {
		$settings = array('core' => array(
			'context' => 'Testing',
			'phpBinaryPathAndFilename' => '/must/be/set/according/to/schema',
			'subRequestIniEntries' => array('someSetting' => 'withValue', 'someFlagSettingWithoutValue' => '')
		));
		$actual = $this->scriptsMock->_call('buildSubprocessCommand', 'flow:foo:identifier', $settings);

		$this->assertContains(sprintf(' -d %s=%s ', escapeshellarg('someSetting'), escapeshellarg('withValue')), $actual);
		$this->assertContains(sprintf(' -d %s ', escapeshellarg('someFlagSettingWithoutValue')), $actual);
	}
}
