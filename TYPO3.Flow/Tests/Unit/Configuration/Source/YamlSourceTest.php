<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Configuration\Source;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once('vfs/vfsStream.php');

/**
 * Testcase for the YAML configuration source
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class YamlSourceTest extends \F3\Testing\BaseTestCase {

	/**
	 * Sets up this test case
	 *
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('testDirectory'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function returnsEmptyArrayOnNonExistingFile() {
		$configurationSource = new \F3\FLOW3\Configuration\Source\YamlSource();
		$configuration = $configurationSource->load('/ThisFileDoesNotExist');
		$this->assertEquals(array(), $configuration, 'No empty array was returned.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function optionSetInTheConfigurationFileReallyEndsUpInTheArray() {
		$pathAndFilename = __DIR__ . '/../Fixture/YAMLConfigurationFile';
		$configurationSource = new \F3\FLOW3\Configuration\Source\YamlSource();
		$configuration = $configurationSource->load($pathAndFilename);
		$this->assertTrue($configuration['configurationFileHasBeenLoaded'], 'The option has not been set by the fixture.');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function saveWritesArrayToGivenFileAsYAML() {
		$pathAndFilename = \vfsStream::url('testDirectory') . '/YAMLConfiguration';
		$configurationSource = new \F3\FLOW3\Configuration\Source\YamlSource();
		$configurationSource->save($pathAndFilename, array('configurationFileHasBeenLoaded' => TRUE));

		$yaml = 'configurationFileHasBeenLoaded: true' . chr(10);
		$this->assertContains($yaml, file_get_contents($pathAndFilename . '.yaml'), 'Configuration was not written to the file.');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function saveWritesDoesNotOverwriteExistingHeaderCommentsIfFileExists() {
		$pathAndFilename = \vfsStream::url('testDirectory') . '/YAMLConfiguration';
		$comment = '# This comment should stay' . chr(10) . 'Test: foo' . chr(10);
		file_put_contents($pathAndFilename . '.yaml', $comment);

		$configurationSource = new \F3\FLOW3\Configuration\Source\YamlSource();
		$configurationSource->save($pathAndFilename, array('configurationFileHasBeenLoaded' => TRUE));

		$yaml = file_get_contents($pathAndFilename . '.yaml');
		$this->assertContains('# This comment should stay', $yaml, 'Header comment was removed from file.');
		$this->assertNotContains('Test: foo', $yaml);
	}
}
?>