<?php
namespace TYPO3\FLOW3\Tests\Unit\Resource;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the resource manager
 *
 */
class ResourceManagerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Foo'));
	}

	/**
	 * This test indeed messes with some of the static stuff concerning our
	 * StreamWrapperAdapter setup. But since the dummy stream wrapper is removed again,
	 * this does not do any harm. And registering the "real" wrappers a second
	 * time doesn't do harm, either.
	 *
	 * What is an issue is the static object manager being set to a mocked one,
	 * be careful...
	 *
	 * @test
	 */
	public function initializeRegistersFoundStreamWrappers() {
		$wrapperClassName = 'MockWrapper' . md5(uniqid(mt_rand(), TRUE));
		$wrapperSchemeName = $wrapperClassName . 'scheme';
		eval('class ' . $wrapperClassName . ' extends \TYPO3\FLOW3\Resource\Streams\ResourceStreamWrapper { static public function getScheme() { return \'' . $wrapperSchemeName . '\'; } }');
		$mockStreamWrapperAdapter = new $wrapperClassName();

		$streamWrapperAdapterReflection = new \ReflectionClass('TYPO3\FLOW3\Resource\Streams\StreamWrapperAdapter');
		$property = $streamWrapperAdapterReflection->getProperty('objectManager');
		$property->setAccessible(TRUE);
		$originalObjectManager = $property->getValue();

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('getAllImplementationClassNamesForInterface')->with('TYPO3\FLOW3\Resource\Streams\StreamWrapperInterface')->will($this->returnValue(array(get_class($mockStreamWrapperAdapter))));

		$resourceManager = new \TYPO3\FLOW3\Resource\ResourceManager();
		$resourceManager->injectObjectManager($mockObjectManager);
		$resourceManager->injectReflectionService($mockReflectionService);
		$resourceManager->initialize();

		$this->assertContains(get_class($mockStreamWrapperAdapter), \TYPO3\FLOW3\Resource\Streams\StreamWrapperAdapter::getRegisteredStreamWrappers());
		$this->assertArrayHasKey($wrapperSchemeName, \TYPO3\FLOW3\Resource\Streams\StreamWrapperAdapter::getRegisteredStreamWrappers());
		$this->assertContains($wrapperSchemeName, stream_get_wrappers());
		stream_wrapper_unregister($wrapperSchemeName);

			// set the real object factory again...
		if ($originalObjectManager !== NULL) {
			\TYPO3\FLOW3\Resource\Streams\StreamWrapperAdapter::injectObjectManager($originalObjectManager);
		}
	}

	/**
	 * @test
	 */
	public function publishPublicPackageResourcesPublishesStaticResourcesOfActivePackages() {
		$settings = array('resource' => array('publishing' => array('detectPackageResourceChanges' => TRUE)));

		$mockStatusCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE);
		$mockStatusCache->expects($this->once())->method('set')->with('packageResourcesPublished', 'y', array(\TYPO3\FLOW3\Cache\Frontend\FrontendInterface::TAG_PACKAGE));

		$mockPackage = $this->getMock('TYPO3\FLOW3\Package\PackageInterface', array(), array(), '', FALSE);
		$mockPackage->expects($this->exactly(2))->method('getResourcesPath')->will($this->onConsecutiveCalls('Packages/Foo/Resources/', 'Packages/Bar/Resources/'));

		$mockResourcePublisher = $this->getMock('TYPO3\FLOW3\Resource\Publishing\ResourcePublisher', array(), array(), '', FALSE);
		$mockResourcePublisher->expects($this->at(0))->method('publishStaticResources')->with('Packages/Foo/Resources/Public/', 'Packages/Foo/');
		$mockResourcePublisher->expects($this->at(1))->method('publishStaticResources')->with('Packages/Bar/Resources/Public/', 'Packages/Bar/');


		$resourceManager = new \TYPO3\FLOW3\Resource\ResourceManager();
		$resourceManager->injectResourcePublisher($mockResourcePublisher);
		$resourceManager->injectStatusCache($mockStatusCache);
		$resourceManager->injectSettings($settings);

		$resourceManager->publishPublicPackageResources(array('Foo' => $mockPackage, 'Bar' => $mockPackage));
	}

	/**
	 * @test
	 */
	public function publishPublicPackageResourcesStoresThePublishingStatusInACacheDoesntPublishResourcesAgainIfSettingsSaySo() {
		$settings = array('resource' => array('publishing' => array('detectPackageResourceChanges' => FALSE)));

		$mockStatusCache = $this->getMock('TYPO3\FLOW3\Cache\Frontend\StringFrontend', array(), array(), '', FALSE);
		$mockStatusCache->expects($this->once())->method('has')->with('packageResourcesPublished')->will($this->returnValue(TRUE));

		$mockPackage = $this->getMock('TYPO3\FLOW3\Package\PackageInterface', array(), array(), '', FALSE);

		$mockResourcePublisher = $this->getMock('TYPO3\FLOW3\Resource\Publishing\ResourcePublisher', array(), array(), '', FALSE);
		$mockResourcePublisher->expects($this->never())->method('publishStaticResource');


		$resourceManager = new \TYPO3\FLOW3\Resource\ResourceManager();
		$resourceManager->injectResourcePublisher($mockResourcePublisher);
		$resourceManager->injectStatusCache($mockStatusCache);
		$resourceManager->injectSettings($settings);

		$resourceManager->publishPublicPackageResources(array('Foo' => $mockPackage, 'Bar' => $mockPackage));
	}

	/**
	 * @test
	 */
	public function getPersistentResourcesStorageBaseUriProvidesTheUriAtAWellKnownPlace() {
		$resourceManager = $this->getAccessibleMock('\TYPO3\FLOW3\Resource\ResourceManager', array('dummy'), array(), '', FALSE);
		$resourceManager->_set('persistentResourcesStorageBaseUri', 'vfs://Foo/Bar/');

		$actualUri = $resourceManager->getPersistentResourcesStorageBaseUri();
		$this->assertSame('vfs://Foo/Bar/', $actualUri);
	}

	/**
	 * @return \TYPO3\FLOW3\Resource\ResourceManager
	 */
	protected function setupResourceManager() {
		file_put_contents('vfs://Foo/SomeResource.txt', '12345');

		mkdir('vfs://Foo/Temporary');
		mkdir('vfs://Foo/Persistent');
		mkdir('vfs://Foo/Persistent/Resources');

		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/Temporary/'));

		$resourceManager = $this->getAccessibleMock('\TYPO3\FLOW3\Resource\ResourceManager', array('dummy'), array(), '', FALSE);
		$resourceManager->_set('persistentResourcesStorageBaseUri', 'vfs://Foo/Persistent/Resources/');
		$resourceManager->_set('importedResources', new \SplObjectStorage());
		$resourceManager->injectEnvironment($mockEnvironment);

		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$resourceManager->injectPersistenceManager($mockPersistenceManager);

		return $resourceManager;
	}

	/**
	 * Note: this test triggers a warning from chmod about a file not existing.
	 * This is a limitation of chmod() which does not work with stream wrappers.
	 *
	 * @test
	 * @outputBuffering enabled
	 *     ... because the chmod call in ResourceManager emits a warningmaking this fail in strict mode
	 */
	public function importResourceImportsTheGivenFileAndReturnsAResourceObject() {
		$resourceManager = $this->setupResourceManager();
		$hash = sha1_file('vfs://Foo/SomeResource.txt');

		$actualResource = $resourceManager->importResource('vfs://Foo/SomeResource.txt');
		$this->assertEquals('SomeResource.txt', $actualResource->getFileName());
		$this->assertEquals($hash, $actualResource->getResourcePointer()->getHash());

		$this->assertFileEquals('vfs://Foo/SomeResource.txt', 'vfs://Foo/Persistent/Resources/' . $hash);
	}

	/**
	 * Note: this test triggers a warning from chmod about a file not existing.
	 * This is a limitation of chmod() which does not work with stream wrappers.
	 *
	 * @test
	 * @outputBuffering enabled
	 *     ... because the chmod call in ResourceManager emits a warningmaking this fail in strict mode
	 */
	public function getImportedResourcesReturnsAListOfResourceObjectsAndSomeInformationAboutTheirImport() {
		$resourceManager = $this->setupResourceManager();

		$resourceManager->importResource('vfs://Foo/SomeResource.txt');
		$importedResources = $resourceManager->getImportedResources();
		foreach ($importedResources as $importedResource) {
			$this->assertSame('SomeResource.txt', $importedResources[$importedResource]['originalFilename']);
		}
	}

	/**
	 * @test
	 * @outputBuffering enabled
	 *     ... because the chmod call in ResourceManager emits a warningmaking this fail in strict mode
	 */
	public function createResourceFromContentStoresTheContentInTheCorrectFileAndReturnsTheCorrespondingResourceObject() {
		$resourceManager = $this->setupResourceManager();

		$fileName = 'myFile.txt';
		$content = 'some content';
		$resultResource = $resourceManager->createResourceFromContent($content, $fileName);

		$this->assertTrue(file_exists('vfs://Foo/Persistent/Resources/' . sha1($content)));
		$this->assertEquals($content, file_get_contents('vfs://Foo/Persistent/Resources/' . sha1($content)));
		$this->assertEquals($fileName, $resultResource->getFileName());
		$this->assertEquals('txt', $resultResource->getFileExtension());
		$this->assertEquals(sha1($content), $resultResource->getResourcePointer()->getHash());
	}

	/**
	 * @test
	 */
	public function importResourceReturnsFalseForPhpFiles() {
		$resourceManager = $this->setupResourceManager();

		$this->assertFalse($resourceManager->importResource('vfs://Foo/SomeResource.php'));
	}

	/**
	 * Note: this test triggers a warning from chmod about a file not existing.
	 * This is a limitation of chmod() which does not work with stream wrappers.
	 *
	 * @test
	 * @outputBuffering enabled
	 *     ... because the chmod call in ResourceManager emits a warningmaking this fail in strict mode
	 */
	public function importResourceWorksForFilesWithoutFileEnding() {
		$resourceManager = $this->setupResourceManager();

		file_put_contents('vfs://Foo/bar', 'Hello world');

		$resource = $resourceManager->importResource('vfs://Foo/bar');
		$this->assertInstanceOf('TYPO3\FLOW3\Resource\Resource', $resource);

		$hash = sha1_file('vfs://Foo/bar');

		$this->assertEquals($hash, $resource->getResourcePointer()->getHash());
		$this->assertFileEquals('vfs://Foo/bar', 'vfs://Foo/Persistent/Resources/' . $hash);
	}
}
?>