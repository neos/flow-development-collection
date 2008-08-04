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
 * @version $Id:F3_FLOW3_AOP_FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the cache to file backend
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_AOP_FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Cache_Backend_FileTest extends F3_Testing_BaseTestCase {

	/**
	 * @var F3_FLOW3_Cache_Backend_File If set, the tearDown() method will clean up the cache subdirectory used by this unit test.
	 */
	protected $backend;

	/**
	 * Sets up this testcase
	 *
	 * @return void
	 */
	public function setUp() {
		$this->backend = NULL;
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isPrototype() {
		$backend1 = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());
		$backend2 = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());
		$this->assertNotSame($backend1, $backend2, 'File Backend seems to be singleton!');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function defaultCacheDirectoryIsWritable() {
		$backend = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());
		$propertyReflection = new F3_FLOW3_Reflection_Property($backend, 'cacheDirectory');
		$cacheDirectory = $propertyReflection->getValue($backend);
		$this->assertTrue(is_writable($cacheDirectory), 'The default cache directory "' . $cacheDirectory . '" is not writable.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCacheDirectoryThrowsExceptionOnNonWritableDirectory() {
		if (DIRECTORY_SEPARATOR == '\\') {
			$this->markTestSkipped('test not reliable in Windows environment');
		}
		$directoryName = '/sbin';
		$backend = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());
		try {
			$backend->setCacheDirectory($directoryName);
			$this->fail('setCacheDirectory() to non-writable directory did not result in an exception.');
		} catch (F3_FLOW3_Cache_Exception $exception) {

		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCacheDirectoryReturnsThePreviouslySetDirectory() {
		$environment = $this->componentFactory->getComponent('F3_FLOW3_Utility_Environment');
		$backend = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());

		$directory = $environment->getPathToTemporaryDirectory();
		$backend->setCacheDirectory($directory);
		$this->assertEquals($directory, $backend->getCacheDirectory(), 'getDirectory() did not return the expected value.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function saveRejectsInvalidIdentifiers() {
		$cache = $this->getMock('F3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has',  'remove'), array(), '', FALSE);
		$context = $this->componentManager->getContext();
		$backend = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $context);
		$data = 'Some data';
		$this->backend = $backend;
		$backend->setCache($cache);

		foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#', 'some&') as $entryIdentifier) {
			try {
				$backend->save($entryIdentifier, $data);
				$this->fail('save() did no reject the entry identifier "' . $entryIdentifier . '".');
			} catch (InvalidArgumentException $exception) {
			}
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function saveThrowsExceptionIfDataIsNotAString() {
		$cache = $this->getMock('F3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has',  'remove'), array(), '', FALSE);

		$data = array('Some data');
		$entryIdentifier = 'BackendFileTest';

		$context = $this->componentManager->getContext();
		$backend = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $context);
		$this->backend = $backend;
		$backend->setCache($cache);
		try {
			$backend->save($entryIdentifier, $data);
			$this->fail('Backend did not throw an exception.');
		} catch (F3_FLOW3_Cache_Exception_InvalidData $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function saveReallySavesToTheSpecifiedDirectory() {
		$cache = $this->getMock('F3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has',  'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$context = $this->componentManager->getContext();

		$backend = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $context);
		$this->backend = $backend;
		$backend->setCache($cache);
		$backend->save($entryIdentifier, $data);

		$cacheDirectory = $backend->getCacheDirectory();
		$pattern = $cacheDirectory . $context . '/Data/UnitTestCache/' . $entryIdentifierHash{0} . '/' . $entryIdentifierHash{1} . '/????-??-?????;??;???_' . $entryIdentifier;
		$filesFound = glob($pattern);
		$this->assertTrue(is_array($filesFound), 'filesFound was no array.');

		$retrievedData = file_get_contents(array_pop($filesFound));
		$this->assertEquals($data, $retrievedData, 'The original and the retrieved data don\'t match.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function saveRemovesAnAlreadyExistingCacheEntryForTheSameIdentifier() {
		$cache = $this->getMock('F3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has',  'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data1 = 'some data' . microtime();
		$data2 = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemoveBeforeSaveTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$context = $this->componentManager->getContext();

		$backend = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $context);
		$this->backend = $backend;
		$backend->setCache($cache);
		$backend->save($entryIdentifier, $data1, array(), 500);
		$backend->save($entryIdentifier, $data2, array(), 200);

		$cacheDirectory = $backend->getCacheDirectory();
		$pattern = $cacheDirectory . $context . '/Data/UnitTestCache/' . $entryIdentifierHash{0} . '/' . $entryIdentifierHash{1} . '/????-??-?????;??;???_' . $entryIdentifier ;
		$filesFound = glob($pattern);
		$this->assertEquals(1, count($filesFound), 'There was not exactly one cache entry.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function saveReallySavesSpecifiedTags() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('F3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has',  'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue($cacheIdentifier));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$context = $this->componentManager->getContext();

		$backend = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $context);
		$this->backend = $backend;
		$backend->setCache($cache);
		$tagsDirectory = $backend->getCacheDirectory() . $context . '/Tags/';

		$backend->save($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));

		$this->assertTrue(is_dir($tagsDirectory . 'UnitTestTag%tag1'), 'Tag directory UnitTestTag%tag1 does not exist.');
		$this->assertTrue(is_dir($tagsDirectory . 'UnitTestTag%tag2'), 'Tag directory UnitTestTag%tag2 does not exist.');

		$filename = $tagsDirectory . 'UnitTestTag%tag1/' . $cacheIdentifier . '_' . $entryIdentifier;
		$this->assertTrue(file_exists($filename), 'File "' . $filename . '" does not exist.');

		$filename = $tagsDirectory . 'UnitTestTag%tag2/' . $cacheIdentifier . '_' . $entryIdentifier;
		$this->assertTrue(file_exists($filename), 'File "' . $filename . '" does not exist.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadReturnsContentOfTheCorrectCacheFile() {
		$cache = $this->getMock('F3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has',  'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());
		$this->backend = $backend;
		$backend->setCache($cache);

		$entryIdentifier = 'BackendFileTest';

		$data = 'some data' . microtime();
		$backend->save($entryIdentifier, $data, array(), 500);

		$data = 'some other data' . microtime();
		$backend->save($entryIdentifier, $data, array(), 100);

		$loadedData = $backend->load($entryIdentifier);
		$this->assertEquals($data, $loadedData, 'The original and the retrieved data don\'t match.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasReturnsTheCorrectResult() {
		$cache = $this->getMock('F3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has',  'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());
		$this->backend = $backend;
		$backend->setCache($cache);

		$entryIdentifier = 'BackendFileTest';

		$data = 'some data' . microtime();
		$backend->save($entryIdentifier, $data);

		$this->assertTrue($backend->has($entryIdentifier), 'has() did not return TRUE.');
		$this->assertFALSE($backend->has($entryIdentifier . 'Not'), 'has() did not return FALSE.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 *
	 */
	public function removeReallyRemovesACacheEntry() {
		$cache = $this->getMock('F3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemovalTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$context = $this->componentManager->getContext();
		$backend = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $context);
		$this->backend = $backend;
		$cacheDirectory = $backend->getCacheDirectory();
		$backend->setCache($cache);

		$pattern = $cacheDirectory . $context . '/Data/UnitTestCache/' . $entryIdentifierHash{0} . '/' . $entryIdentifierHash{1} . '/????-??-?????;??;???_' . $entryIdentifier;

		$backend->save($entryIdentifier, $data);
		$filesFound = glob($pattern);
		$this->assertTrue(is_array($filesFound) && count($filesFound) > 0, 'The cache entry does not exist.');

		$backend->remove($entryIdentifier);
		$filesFound = glob($pattern);
		$this->assertTrue(count($filesFound) == 0, 'The cache entry still exists.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 *
	 */
	public function removeReallyRemovesTagsOfRemovedEntry() {
		$cache = $this->getMock('F3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has',  'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';

		$context = $this->componentManager->getContext();

		$backend = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $context);
		$this->backend = $backend;
		$backend->setCache($cache);

		$tagsDirectory = $backend->getCacheDirectory() . $context . '/Tags/';

		$backend->save($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));
		$backend->remove($entryIdentifier);

		$this->assertTrue(!file_exists($tagsDirectory . 'UnitTestTag%tag1/' . $entryIdentifier), 'File "' . $tagsDirectory . 'UnitTestTag%tag1/' . $entryIdentifier . '" still exists.');
		$this->assertTrue(!file_exists($tagsDirectory . 'UnitTestTag%tag2/' . $entryIdentifier), 'File "' . $tagsDirectory . 'UnitTestTag%tag2/' . $entryIdentifier . '" still exists.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findByTagFindsCacheEntriesWithSpecifiedTag() {
		$cache = $this->getMock('F3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has',  'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());
		$this->backend = $backend;
		$backend->setCache($cache);

		$data = 'some data' . microtime();
		$backend->save('BackendFileTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$backend->save('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$backend->save('BackendFileTest3', $data, array('UnitTestTag%test'));

		$expectedEntry = 'BackendFileTest2';

		$actualEntries = $backend->findEntriesByTag('UnitTestTag%special');
		$this->assertTrue(is_array($actualEntries), 'actualEntries is not an array.');

		$this->assertEquals($expectedEntry, array_pop($actualEntries));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushRemovesAllCacheEntriesAndRelatedTags() {
		$context = $this->componentManager->getContext();
		$data = 'some data' . microtime();

		$cache = $this->getMock('F3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has',  'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());
		$backend->setCache($cache);
		$this->backend = $backend;
		$tagsDirectory = $backend->getCacheDirectory() . $context . '/Tags/';
		$cacheDirectory = $backend->getCacheDirectory() . $context . '/Data/UnitTestCache/';

		$backend->save('BackendFileTest1', $data, array('UnitTestTag%test'));
		$backend->save('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$backend->save('BackendFileTest3', $data, array('UnitTestTag%test'));

		$backend->flush();

		$pattern = $cacheDirectory . '*/*/*';
		$filesFound = glob($pattern);
		$this->assertTrue(count($filesFound) == 0, 'Still files in the cache directory');

		$entryIdentifier = 'BackendFileTest1';
		$this->assertTrue(!file_exists($tagsDirectory . 'UnitTestTag%test/' . $entryIdentifier), 'File "' . $tagsDirectory . 'UnitTestTag%test/' . $entryIdentifier . '" still exists.');
		$entryIdentifier = 'BackendFileTest2';
		$this->assertTrue(!file_exists($tagsDirectory . 'UnitTestTag%test/' . $entryIdentifier), 'File "' . $tagsDirectory . 'UnitTestTag%test/' . $entryIdentifier . '" still exists.');
		$entryIdentifier = 'BackendFileTest3';
		$this->assertTrue(!file_exists($tagsDirectory . 'UnitTestTag%test/' . $entryIdentifier), 'File "' . $tagsDirectory . 'UnitTestTag%test/' . $entryIdentifier . '" still exists.');
		$this->assertTrue(!file_exists($tagsDirectory . 'UnitTestTag%special/' . $entryIdentifier), 'File "' . $tagsDirectory . 'UnitTestTag%special/' . $entryIdentifier . '" still exists.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		$cache = $this->getMock('F3_FLOW3_Cache_AbstractCache', array('getIdentifier', 'save', 'load', 'has',  'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->componentFactory->getComponent('F3_FLOW3_Cache_Backend_File', $this->componentManager->getContext());
		$this->backend = $backend;
		$backend->setCache($cache);

		$data = 'some data' . microtime();
		$backend->save('BackendFileTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$backend->save('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$backend->save('BackendFileTest3', $data, array('UnitTestTag%test'));

		$backend->flushByTag('UnitTestTag%special');

		$this->assertTrue($backend->has('BackendFileTest1'), 'BackendFileTest1');
		$this->assertFalse($backend->has('BackendFileTest2'), 'BackendFileTest2');
		$this->assertTrue($backend->has('BackendFileTest3'), 'BackendFileTest3');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function tearDown() {
		if (is_object($this->backend)) {
			$context = $this->componentManager->getContext();
			$directory = $this->backend->getCacheDirectory() . $context . '/Data/UnitTestCache';
			if (is_dir($directory)) F3_FLOW3_Utility_Files::removeDirectoryRecursively($directory);

			$pattern = $this->backend->getCacheDirectory() . $context . '/Tags/UnitTestTag%*/*';
			$filesFound = glob($pattern);
			if ($filesFound === FALSE || count($filesFound) == 0) return;

			foreach ($filesFound as $filename) {
				unlink($filename);
			}

			$pattern = $this->backend->getCacheDirectory() . $context . '/Tags/UnitTestTag%*';
			$directoriesFound = glob($pattern);
			if ($directoriesFound === FALSE || count($directoriesFound) == 0) return;

			foreach ($directoriesFound as $directory) {
				rmdir($directory);
			}
		}
	}
}
?>
