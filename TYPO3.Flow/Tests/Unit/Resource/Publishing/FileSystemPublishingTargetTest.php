<?php
namespace TYPO3\FLOW3\Tests\Unit\Resource\Publishing;

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
 * Testcase for the File System Publishing Target
 *
 * @covers \TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget<extended>
 */
class FileSystemPublishingTargetTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Foo'));
	}

	/**
	 * Checks if the package autoloader loads classes from subdirectories.
	 *
	 * @test
	 */
	public function initalizeObjectCreatesDirectoriesAndDetectsTheResourcesBaseUri() {
		$publishingTarget = $this->getAccessibleMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array('detectResourcesBaseUri'));
		$publishingTarget->expects($this->once())->method('detectResourcesBaseUri');

		$publishingTarget->_set('resourcesPublishingPath', 'vfs://Foo/Web/_Resources/');
		$publishingTarget->initializeObject();

		$this->assertFileExists('vfs://Foo/Web/_Resources');
		$this->assertFileExists('vfs://Foo/Web/_Resources/Persistent');
	}

	/**
	 * @test
	 */
	public function publishStaticResourcesReturnsFalseIfTheGivenSourceDirectoryDoesntExist() {
		$publishingTarget = $this->getAccessibleMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array('dummy'));
		$this->assertFalse($publishingTarget->publishStaticResources('vfs://Foo/Bar', 'x'));
	}

	/**
	 * @test
	 */
	public function publishStaticResourcesMirrorsRecursivelyAllFilesExceptPHPFoundInTheSpecifiedDirectory() {
		mkdir('vfs://Foo/Sources');
		mkdir('vfs://Foo/Sources/SubDirectory');
		mkdir('vfs://Foo/Sources/SubDirectory/SubSubDirectory');

		file_put_contents('vfs://Foo/Sources/file1.txt', 1);
		file_put_contents('vfs://Foo/Sources/file2.txt', 1);
		file_put_contents('vfs://Foo/Sources/SubDirectory/file2.txt', 1);
		file_put_contents('vfs://Foo/Sources/SubDirectory/SubSubDirectory/file3.txt', 1);
		file_put_contents('vfs://Foo/Sources/SubDirectory/SubSubDirectory/file4.php', 1);
		file_put_contents('vfs://Foo/Sources/SubDirectory/SubSubDirectory/file5.jpg', 1);

		mkdir('vfs://Foo/Web');
		mkdir('vfs://Foo/Web/_Resources');


		$publishingTarget = $this->getAccessibleMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array('mirrorFile', 'realpath'));
		$publishingTarget->expects($this->at(0))->method('realpath')->will($this->returnCallback(function($path) {
			return $path;
		}));
		$publishingTarget->_set('resourcesPublishingPath', 'vfs://Foo/Web/_Resources/');

		$publishingTarget->expects($this->at(1))->method('mirrorFile')->with('vfs://Foo/Sources/SubDirectory/SubSubDirectory/file3.txt', 'vfs://Foo/Web/_Resources/Static/Bar/SubDirectory/SubSubDirectory/file3.txt');
		$publishingTarget->expects($this->at(2))->method('mirrorFile')->with('vfs://Foo/Sources/SubDirectory/SubSubDirectory/file5.jpg', 'vfs://Foo/Web/_Resources/Static/Bar/SubDirectory/SubSubDirectory/file5.jpg');
		$publishingTarget->expects($this->at(3))->method('mirrorFile')->with('vfs://Foo/Sources/SubDirectory/file2.txt', 'vfs://Foo/Web/_Resources/Static/Bar/SubDirectory/file2.txt');
		$publishingTarget->expects($this->at(4))->method('mirrorFile')->with('vfs://Foo/Sources/file1.txt', 'vfs://Foo/Web/_Resources/Static/Bar/file1.txt');
		$publishingTarget->expects($this->at(5))->method('mirrorFile')->with('vfs://Foo/Sources/file2.txt', 'vfs://Foo/Web/_Resources/Static/Bar/file2.txt');

		$result = $publishingTarget->publishStaticResources('vfs://Foo/Sources', 'Bar');
		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function publishStaticResourcesLinksTheSpecifiedDirectoryIfMirrorModeIsLink() {
		$sourcePath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(realpath(sys_get_temp_dir()), 'FLOW3FileSystemPublishingTargetTestSource'));
		$targetRootPath =  \TYPO3\FLOW3\Utility\Files::concatenatePaths(array(realpath(sys_get_temp_dir()), 'FLOW3FileSystemPublishingTargetTestTarget'));
		$targetPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($targetRootPath, '_Resources'));

		mkdir($sourcePath);
		\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($targetPath);

		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'link'))));

		$publishingTarget = $this->getAccessibleMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array('mirrorFile'));
		$publishingTarget->_set('settings', $settings);
		$publishingTarget->_set('resourcesPublishingPath', $targetPath);

		$publishingTarget->expects($this->never())->method('mirrorFile');

		$this->assertTrue($publishingTarget->publishStaticResources($sourcePath, 'Bar'));
		$this->assertTrue(\TYPO3\FLOW3\Utility\Files::is_link(\TYPO3\FLOW3\Utility\Files::concatenatePaths(array($targetPath, 'Static/Bar'))));

		\TYPO3\FLOW3\Utility\Files::removeDirectoryRecursively($targetRootPath);
		\TYPO3\FLOW3\Utility\Files::removeDirectoryRecursively($sourcePath);
	}

	/**
	 * @test
	 */
	public function publishStaticResourcesDoesNotMirrorAFileIfItAlreadyExistsAndTheModificationTimeIsEqualOrNewer() {
		mkdir('vfs://Foo/Sources');

		file_put_contents('vfs://Foo/Sources/file1.txt', 1);
		file_put_contents('vfs://Foo/Sources/file2.txt', 1);
		file_put_contents('vfs://Foo/Sources/file3.txt', 1);

		\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively('vfs://Foo/Web/_Resources/Static/Bar');

		file_put_contents('vfs://Foo/Web/_Resources/Static/Bar/file2.txt', 1);
		\vfsStreamWrapper::getRoot()->getChild('Web/_Resources/Static/Bar/file2.txt')->lastModified(time() - 5);

		file_put_contents('vfs://Foo/Web/_Resources/Static/Bar/file3.txt', 1);

		$mirrorFileCallback = function($sourcePathAndFilename, $targetPathAndFilename) {
			if ($sourcePathAndFilename === 'vfs://Foo/Sources/file3.txt') {
				throw new \Exception('file3.txt should not have been mirrored.');
			}
		};

		$publishingTarget = $this->getAccessibleMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array('mirrorFile', 'realpath'));
		$publishingTarget->expects($this->any())->method('realpath')->will($this->returnCallback(function($path) {
			return $path;
		}));

		$publishingTarget->_set('resourcesPublishingPath', 'vfs://Foo/Web/_Resources/');

		$publishingTarget->expects($this->exactly(2))->method('mirrorFile')->will($this->returnCallback($mirrorFileCallback));

		$result = $publishingTarget->publishStaticResources('vfs://Foo/Sources', 'Bar');

		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function publishPersistentResourceMirrorsTheGivenResource() {
		$mockResourcePointer = $this->getMock('TYPO3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->atLeastOnce())->method('getHash')->will($this->returnValue('ac9b6187f4c55b461d69e22a57925ff61ee89cb2'));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->atLeastOnce())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->atLeastOnce())->method('getFileExtension')->will($this->returnValue('jpg'));
		$mockResource->expects($this->atLeastOnce())->method('getFilename')->will($this->returnValue('source.jpg'));

		$publishingTarget = $this->getAccessibleMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array('rewriteTitleForUri', 'getPersistentResourceSourcePathAndFilename', 'mirrorFile'));
		$publishingTarget->expects($this->once())->method('getPersistentResourceSourcePathAndFilename')->with($mockResource)->will($this->returnValue('source.jpg'));
		$publishingTarget->expects($this->once())->method('mirrorFile')->with('source.jpg', 'vfs://Foo/Web/Persistent/ac9b6187f4c55b461d69e22a57925ff61ee89cb2.jpg');

		$publishingTarget->_set('resourcesPublishingPath', 'vfs://Foo/Web/');
		$publishingTarget->_set('resourcesBaseUri', 'http://Foo/_Resources/');

		$this->assertSame('http://Foo/_Resources/Persistent/ac9b6187f4c55b461d69e22a57925ff61ee89cb2/source.jpg', $publishingTarget->publishPersistentResource($mockResource));
	}

	/**
	 * @test
	 */
	public function publishPersistentResourceLeavesOutEmptyFileName() {
		$mockResourcePointer = $this->getMock('TYPO3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->atLeastOnce())->method('getHash')->will($this->returnValue('ac9b6187f4c55b461d69e22a57925ff61ee89cb2'));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->atLeastOnce())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->atLeastOnce())->method('getFileExtension')->will($this->returnValue(''));
		$mockResource->expects($this->at(0))->method('getFilename')->will($this->returnValue(''));
		$mockResource->expects($this->at(1))->method('getFilename')->will($this->returnValue(NULL));

		$publishingTarget = $this->getAccessibleMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array('rewriteTitleForUri', 'getPersistentResourceSourcePathAndFilename', 'mirrorFile'));
		$publishingTarget->expects($this->any())->method('getPersistentResourceSourcePathAndFilename')->will($this->returnValue('source.jpg'));

		$publishingTarget->_set('resourcesPublishingPath', 'vfs://Foo/Web/');
		$publishingTarget->_set('resourcesBaseUri', 'http://Foo/_Resources/');

		$this->assertSame('http://Foo/_Resources/Persistent/ac9b6187f4c55b461d69e22a57925ff61ee89cb2', $publishingTarget->publishPersistentResource($mockResource));
		$this->assertSame('http://Foo/_Resources/Persistent/ac9b6187f4c55b461d69e22a57925ff61ee89cb2', $publishingTarget->publishPersistentResource($mockResource));
	}

	/**
	 * @test
	 */
	public function publishPersistentResourceMirrorsTheGivenSourceFileDoesntExist() {
		$mockResourcePointer = $this->getMock('TYPO3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->atLeastOnce())->method('getHash')->will($this->returnValue('ac9b6187f4c55b461d69e22a57925ff61ee89cb2'));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->atLeastOnce())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->atLeastOnce())->method('getFileExtension')->will($this->returnValue('jpg'));

		$publishingTarget = $this->getAccessibleMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array('rewriteTitleForUri', 'getPersistentResourceSourcePathAndFilename'));
		$publishingTarget->expects($this->once())->method('getPersistentResourceSourcePathAndFilename')->with($mockResource)->will($this->returnValue(FALSE));

		$publishingTarget->_set('resourcesPublishingPath', 'vfs://Foo/Web/');

		$this->assertFalse($publishingTarget->publishPersistentResource($mockResource));
	}

	/**
	 * @test
	 */
	public function publishPersistentResourceDoesNotMirrorTheResourceIfItAlreadyExistsInThePublishingDirectory() {
		mkdir('vfs://Foo/Web');
		mkdir('vfs://Foo/Web/Persistent');
		file_put_contents('vfs://Foo/Web/Persistent/ac9b6187f4c55b461d69e22a57925ff61ee89cb2.jpg', 'some data');

		$mockResourcePointer = $this->getMock('TYPO3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->atLeastOnce())->method('getHash')->will($this->returnValue('ac9b6187f4c55b461d69e22a57925ff61ee89cb2'));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->atLeastOnce())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));
		$mockResource->expects($this->atLeastOnce())->method('getFileExtension')->will($this->returnValue('jpg'));

		$publishingTarget = $this->getAccessibleMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array('rewriteTitleForUri', 'getPersistentResourceSourcePathAndFilename'));

		$publishingTarget->_set('resourcesPublishingPath', 'vfs://Foo/Web/');
		$publishingTarget->_set('resourcesBaseUri', 'http://host/dir/');

		$publishingTarget->publishPersistentResource($mockResource);
	}

	/**
	 * @test
	 */
	public function unpublishPersistentResourceMirrorsTheGivenResource() {
		$this->marktestSkipped('It seems glob() does not work on vfsStream...');

		$mockResourcePointer = $this->getMock('TYPO3\FLOW3\Resource\ResourcePointer', array(), array(), '', FALSE);
		$mockResourcePointer->expects($this->atLeastOnce())->method('getHash')->will($this->returnValue('ac9b6187f4c55b461d69e22a57925ff61ee89cb2'));

		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);
		$mockResource->expects($this->atLeastOnce())->method('getResourcePointer')->will($this->returnValue($mockResourcePointer));

		mkdir('vfs://Foo/Web');
		mkdir('vfs://Foo/Web/Persistent');
		file_put_contents('vfs://Foo/Web/Persistent/ac9b6187f4c55b461d69e22a57925ff61ee89cb2.jpg', 'some data');

		$publishingTarget = $this->getAccessibleMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array('dummy'));
		$publishingTarget->_set('resourcesPublishingPath', 'vfs://Foo/Web/');

		$this->assertTrue(file_exists('vfs://Foo/Web/Persistent/ac9b6187f4c55b461d69e22a57925ff61ee89cb2.jpg'));
		$this->assertTrue($publishingTarget->unpublishPersistentResource($mockResource));
		$this->assertFalse(file_exists('vfs://Foo/Web/Persistent/ac9b6187f4c55b461d69e22a57925ff61ee89cb2.jpg'));
	}

	/**
	 * @test
	 */
	public function getStaticResourcesWebBaseUriReturnsJustThat() {
		$publishingTarget = $this->getAccessibleMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array('dummy'));
		$publishingTarget->_set('resourcesBaseUri', 'http://host/dir/');

		$this->assertSame('http://host/dir/Static/', $publishingTarget->getStaticResourcesWebBaseUri());
	}

	/**
	 * @test
	 */
	public function getPersistentResourceWebUriJustCallsPublishPersistentResource() {
		$mockResource = $this->getMock('TYPO3\FLOW3\Resource\Resource', array(), array(), '', FALSE);

		$publishingTarget = $this->getAccessibleMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array('publishPersistentResource'));
		$publishingTarget->expects($this->once())->method('publishPersistentResource')->with($mockResource)->will($this->returnValue('http://result'));

		$this->assertSame('http://result', $publishingTarget->getPersistentResourceWebUri($mockResource));
	}

	/**
	 * Because mirrorFile() uses touch() we can't use vfs to mock the file system.
	 *
	 * @test
	 */
	public function mirrorFileCopiesTheGivenFileIfTheSettingSaysSo() {
		$sourcePathAndFilename = tempnam('FLOW3FileSystemPublishingTargetTestSource', '');
		$targetPathAndFilename = tempnam('FLOW3FileSystemPublishingTargetTestTarget', '');

		file_put_contents($sourcePathAndFilename, 'some data');
		touch($sourcePathAndFilename, time() - 5);

		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'copy'))));

		$publishingTarget = $this->getAccessibleMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array('dummy'));
		$publishingTarget->injectSettings($settings);

		$publishingTarget->_call('mirrorFile', $sourcePathAndFilename, $targetPathAndFilename, TRUE);
		$this->assertFileEquals($sourcePathAndFilename, $targetPathAndFilename);

		clearstatcache();
		$this->assertSame(filemtime($sourcePathAndFilename), filemtime($targetPathAndFilename));

		unlink($sourcePathAndFilename);
		unlink($targetPathAndFilename);
	}

	/**
	 * Because mirrorFile() uses touch() we can't use vfs to mock the file system.
	 *
	 * @test
	 */
	public function mirrorFileSymLinksTheGivenFileIfTheSettingSaysSo() {
		$sourcePathAndFilename = tempnam('FLOW3FileSystemPublishingTargetTestSource', '');
		$targetPathAndFilename = tempnam('FLOW3FileSystemPublishingTargetTestTarget', '');

		file_put_contents($sourcePathAndFilename, 'some data');
		touch($sourcePathAndFilename, time() - 5);

		$settings = array('resource' => array('publishing' => array('fileSystem' => array('mirrorMode' => 'link'))));

		$publishingTarget = $this->getAccessibleMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array('dummy'));
		$publishingTarget->_set('settings', $settings);

		$publishingTarget->_call('mirrorFile', $sourcePathAndFilename, $targetPathAndFilename, TRUE);
		$this->assertFileEquals($sourcePathAndFilename, $targetPathAndFilename);
		$this->assertTrue(\TYPO3\FLOW3\Utility\Files::is_link($targetPathAndFilename));

		unlink($sourcePathAndFilename);
		unlink($targetPathAndFilename);
	}

	/**
	 * @test
	 */
	public function detectResourcesBaseUriDetectsUriWithSubDirectoryCorrectly() {
		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getBaseUri')->will($this->returnValue(new \TYPO3\FLOW3\Http\Uri('http://www.server.com/')));

		$expectedBaseUri = 'http://www.server.com/_Resources/';

		$publishingTarget = $this->getAccessibleMock('TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget', array('dummy'));
		$publishingTarget->_set('resourcesPublishingPath', FLOW3_PATH_WEB . '_Resources/');
		$publishingTarget->injectEnvironment($mockEnvironment);

		$publishingTarget->_call('detectResourcesBaseUri');

		$actualBaseUri = $publishingTarget->_get('resourcesBaseUri');
		$this->assertSame($expectedBaseUri, $actualBaseUri);
	}
}
?>