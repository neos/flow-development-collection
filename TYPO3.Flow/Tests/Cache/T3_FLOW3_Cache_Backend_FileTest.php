<?php
declare(ENCODING = 'utf-8');

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
 * @version $Id:T3_FLOW3_AOP_FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the cache to file backend
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:T3_FLOW3_AOP_FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Cache_Backend_FileTest extends T3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isPrototype() {
		$backend1 = $this->componentManager->getComponent('T3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());
		$backend2 = $this->componentManager->getComponent('T3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());
		$this->assertNotSame($backend1, $backend2, 'File Backend seems to be singleton!');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function defaultCacheDirectoryIsWritable() {
		$backend = $this->componentManager->getComponent('T3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());
		$propertyReflection = new T3_FLOW3_Reflection_Property($backend, 'cacheDirectory');
		$cacheDirectory = $propertyReflection->getValue($backend);
		$this->assertTrue(is_writable($cacheDirectory), 'The default cache directory "' . $cacheDirectory . '" is not writable.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCacheDirectoryThrowsExceptionOnNonWritableDirectory() {
		switch (PHP_OS) {
			case 'Darwin' :
				$directoryName = '/private';
				break;
			case 'Linux' :
				$directoryName = '/sbin';
				break;
			default :
				throw new PHPUnit_Framework_IncompleteTestError('Didn\'t know how a non-writable directory for this platform.');
		}
		$backend = $this->componentManager->getComponent('T3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());
		try {
			$backend->setCacheDirectory($directoryName);
			$this->fail('setCacheDirectory() to non-writable directory did not result in an exception.');
		} catch (T3_FLOW3_Cache_Exception $exception) {

		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCacheDirectoryReturnsThePreviouslySetDirectory() {
		$environment = $this->componentManager->getComponent('T3_FLOW3_Utility_Environment');
		$backend = $this->componentManager->getComponent('T3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());

		$directory = $environment->getPathToTemporaryDirectory();
		$backend->setCacheDirectory($directory);
		$this->assertEquals($directory, $backend->getCacheDirectory(), 'getDirectory() did not return the expected value.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function saveReallySavesToTheSpecifiedDirectory() {
		$cache = $this->getMock('T3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'load', 'save'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));

		$data = 'some data' . microtime();
		$dataHash = sha1($data);

		$entryIdentifier = 'BackendFileTest';
		$context = $this->componentManager->getContext();

		$backend = $this->componentManager->getComponent('T3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());
		$backend->setCache($cache);
		$backend->save($data, $entryIdentifier);

		$cacheDirectory = $backend->getCacheDirectory();
		$pattern = $cacheDirectory . $context . '/Cache/test/' . $dataHash{0} . '/' . $dataHash{1} . '/????-??-?????;??;???_' . $entryIdentifier . '*.cachedata';
		$filesFound = glob($pattern);
		$this->assertTrue(is_array($filesFound), 'filesFound was no array.');

		$retrievedData = file_get_contents(array_pop($filesFound));
		$this->assertEquals($data, $retrievedData, 'The original and the retrieved data don\'t match.');

		T3_FLOW3_Utility_Files::removeDirectoryRecursively($cacheDirectory);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadReturnsContentOfTheCorrectCacheFile() {
		$cache = $this->getMock('T3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'load', 'save'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('test'));

		$data = 'some data' . microtime();
		$dataHash = sha1($data);

		$entryIdentifier = 'BackendFileTest';
		$context = $this->componentManager->getContext();

		$backend = $this->componentManager->getComponent('T3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());
		$backend->setCache($cache);
		$backend->save($data, $entryIdentifier);

		$loadedData = $backend->load($entryIdentifier);
		$this->assertEquals($data, $loadedData, 'The original and the retrieved data don\'t match.');

		T3_FLOW3_Utility_Files::removeDirectoryRecursively($backend->getCacheDirectory());
	}
}
?>