<?php
namespace TYPO3\Flow\Tests\Unit\Configuration\Source;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use org\bovigo\vfs\vfsStream;

/**
 * Testcase for the YAML configuration source
 *
 */
class YamlSourceTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		vfsStream::setup('testDirectory');
	}

	/**
	 * @test
	 */
	public function returnsEmptyArrayOnNonExistingFile() {
		$configurationSource = new \TYPO3\Flow\Configuration\Source\YamlSource();
		$configuration = $configurationSource->load('/ThisFileDoesNotExist');
		$this->assertEquals(array(), $configuration, 'No empty array was returned.');
	}

	/**
	 * @test
	 */
	public function optionSetInTheConfigurationFileReallyEndsUpInTheArray() {
		$pathAndFilename = __DIR__ . '/../Fixture/YAMLConfigurationFile';
		$configurationSource = new \TYPO3\Flow\Configuration\Source\YamlSource();
		$configuration = $configurationSource->load($pathAndFilename);
		$this->assertTrue($configuration['configurationFileHasBeenLoaded'], 'The option has not been set by the fixture.');
	}

	/**
	 * @test
	 */
	public function saveWritesArrayToGivenFileAsYAML() {
		$pathAndFilename = vfsStream::url('testDirectory') . '/YAMLConfiguration';
		$configurationSource = new \TYPO3\Flow\Configuration\Source\YamlSource();
		$mockConfiguration = array(
			'configurationFileHasBeenLoaded' => TRUE,
			'foo' => array(
				'bar' => 'Baz'
			)
		);
		$configurationSource->save($pathAndFilename, $mockConfiguration);

		$yaml = 'configurationFileHasBeenLoaded: true' . chr(10) . 'foo:' . chr(10) . '  bar: Baz' . chr(10);
		$this->assertContains($yaml, file_get_contents($pathAndFilename . '.yaml'), 'Configuration was not written to the file.');
	}

	/**
	 * @test
	 */
	public function saveWritesDoesNotOverwriteExistingHeaderCommentsIfFileExists() {
		$pathAndFilename = vfsStream::url('testDirectory') . '/YAMLConfiguration';
		$comment = '# This comment should stay' . chr(10) . 'Test: foo' . chr(10);
		file_put_contents($pathAndFilename . '.yaml', $comment);

		$configurationSource = new \TYPO3\Flow\Configuration\Source\YamlSource();
		$configurationSource->save($pathAndFilename, array('configurationFileHasBeenLoaded' => TRUE));

		$yaml = file_get_contents($pathAndFilename . '.yaml');
		$this->assertContains('# This comment should stay' . chr(10) . chr(10), $yaml, 'Header comment was removed from file.');
		$this->assertNotContains('Test: foo', $yaml);
	}

	/**
	 * @test
	 */
	public function yamlFileIsParsedToArray() {
		$expectedConfiguration = array(
			'configurationFileHasBeenLoaded' => TRUE,
			'TYPO3' => array(
				'Flow' => array(
					'something' => 'foo',
					'@bar' => 1,
					'aboolean' => TRUE
				)
			)
		);
		$pathAndFilename = __DIR__ . '/../Fixture/YAMLConfigurationFile';
		$configurationSource = new \TYPO3\Flow\Configuration\Source\YamlSource();
		$configuration = $configurationSource->load($pathAndFilename);
		$this->assertSame($expectedConfiguration, $configuration);
	}

	/**
	 * @test
	 */
	public function splitConfigurationFilesAreMergedAsExpected() {
		$expectedConfiguration = array(
			'configurationFileHasBeenLoaded' => TRUE,
			'TYPO3' => array(
				'Flow' => array(
					'something' => 'zzz',
					'@bar' => 1,
					'aboolean' => TRUE
				)
			)
		);
		$pathAndFilename = __DIR__ . '/../Fixture/SplitYamlConfigurationFile';
		$configurationSource = new \TYPO3\Flow\Configuration\Source\YamlSource();
		$configuration = $configurationSource->load($pathAndFilename, TRUE);
		$this->assertSame($expectedConfiguration, $configuration);
	}

}
?>