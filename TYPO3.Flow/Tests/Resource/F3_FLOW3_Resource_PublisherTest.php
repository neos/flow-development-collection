<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Resource;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::Object::ClassLoaderTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the resource publisher
 *
 * @package FLOW3
 * @version $Id:F3::FLOW3::Object::ClassLoaderTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PublisherTest extends F3::Testing::BaseTestCase {

	/**
	 * @var string
	 */
	protected $publicResourcePath;

	/**
	 * @var F3::FLOW3::Resource::Publisher
	 */
	protected $publisher;

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$environment = $this->objectManager->getObject('F3::FLOW3::Utility::Environment');
		$this->publicResourcePath = $environment->getPathToTemporaryDirectory() . uniqid() . '/';

		$metadataCache = $this->getMock('F3::FLOW3::Cache::VariableCache', array(), array(), '', FALSE);

		$this->publisher = new F3::FLOW3::Resource::Publisher();
		$this->publisher->injectObjectFactory($this->objectFactory);
		$this->publisher->initializeMirrorDirectory($this->publicResourcePath);
		$this->publisher->setMetadataCache($metadataCache);
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
		$URI = new F3::FLOW3::Property::DataType::URI('file://TestPackage/Public/TestTemplate.html');
		$expectedMetadata = array(
			'URI' => $URI,
			'path' => $this->publicResourcePath . 'TestPackage/Public',
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
	 * @todo Due to no metadata caching the check for subsequent calls is somewhat bogus. Needs to be fixed.
	 */
	public function canGetMetadataForURI() {
		$URI = new F3::FLOW3::Property::DataType::URI('file://TestPackage/Public/TestTemplate.html');
		$expectedMetadata = array(
			'URI' => $URI,
			'path' => $this->publicResourcePath . 'TestPackage/Public',
			'name' => 'TestTemplate.html',
			'mimeType' => 'text/html',
			'mediaType' => 'text',
		);

		$extractedMetadata1 = $this->publisher->getMetadata($URI);
		$extractedMetadata2 = $this->publisher->getMetadata($URI);

		$this->assertEquals($expectedMetadata, $extractedMetadata1, 'The returned metadata was not as expected.');
		$this->assertEquals($extractedMetadata1, $extractedMetadata2, 'The metadata returned in subsequent calls was not equal.');
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
		if (is_dir($this->publicResourcePath)) {
			F3::FLOW3::Utility::Files::removeDirectoryRecursively($this->publicResourcePath);
		}
	}

}

?>