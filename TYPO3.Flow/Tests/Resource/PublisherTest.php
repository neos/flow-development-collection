<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Resource;

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

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the resource publisher
 *
 * @package FLOW3
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PublisherTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var string
	 */
	protected $publicResourcePath;

	/**
	 * @var \F3\FLOW3\Resource\Publisher
	 */
	protected $publisher;

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		if (PHP_SAPI === 'cli') {
			$this->markTestSkipped('Skipping resource publisher tests in CLI mode (for now)');
			return;
		}

		$environment = new \F3\FLOW3\Utility\Environment();
		$environment->setTemporaryDirectoryBase(FLOW3_PATH_DATA . 'Temporary/');
		$this->publicResourcePath = 'Resources/' . uniqid('Test') . '/';
		$metadataCache = $this->getMock('F3\FLOW3\Cache\Frontend\VariableFrontend', array(), array(), '', FALSE);

		$this->publisher = new \F3\FLOW3\Resource\Publisher();
		$this->publisher->setMetadataCache($metadataCache);
		$this->publisher->initializeMirrorDirectory($this->publicResourcePath);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializesMirrorDirectory() {
		$this->assertFileExists($this->publicResourcePath, 'Public resource mirror path has not been set up.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function canExtractResourceMetadataForURI() {
		$URI = new \F3\FLOW3\Property\DataType\URI('file://FLOW3/Public/TestTemplate.html');
		$expectedMetadata = array(
			'URI' => $URI,
			'path' => FLOW3_PATH_WEB . $this->publicResourcePath . 'Packages/FLOW3',
			'name' => 'TestTemplate.html',
			'mimeType' => 'text/html',
			'mediaType' => 'text',
		);

		$extractedMetadata = $this->publisher->extractResourceMetadata($URI);
		$this->assertEquals($expectedMetadata, $extractedMetadata, 'The extracted metadata was not as expected.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function canGetMetadataForURI() {
		$URI = new \F3\FLOW3\Property\DataType\URI('file://FLOW3/Public/TestTemplate.html');
		$expectedMetadata = array(
			'URI' => $URI,
			'path' => FLOW3_PATH_WEB . $this->publicResourcePath . 'Packages/FLOW3',
			'name' => 'TestTemplate.html',
			'mimeType' => 'text/html',
			'mediaType' => 'text',
		);

		$extractedMetadata = $this->publisher->getMetadata($URI);

		$this->assertEquals($expectedMetadata, $extractedMetadata, 'The returned metadata was not as expected.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function canMirrorPublicPackageResources() {
		$this->markTestIncomplete('Test not yet implemented.');
	}


	/**
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function tearDown() {
		if (is_dir(FLOW3_PATH_WEB . $this->publicResourcePath)) {
			\F3\FLOW3\Utility\Files::removeDirectoryRecursively(FLOW3_PATH_WEB . $this->publicResourcePath);
		}
	}

}

?>