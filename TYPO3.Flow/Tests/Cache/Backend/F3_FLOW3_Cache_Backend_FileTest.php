<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Cache\Backend;

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
 * @version $Id:\F3\FLOW3\AOP::FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the cache to file backend
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\AOP::FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class FileTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Cache\Backend\File If set, the tearDown() method will clean up the cache subdirectory used by this unit test.
	 */
	protected $backend;

	/**
	 * Sets up this testcase
	 *
	 * @return void
	 */
	public function setUp() {
		$environment = new \F3\FLOW3\Utility\Environment();
		$environment->setTemporaryDirectoryBase(FLOW3_PATH_DATA . 'Temporary/');

		$this->backend = new \F3\FLOW3\Cache\Backend\File('Testing');
		$this->backend->injectEnvironment($environment);
		$this->backend->initializeObject();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function defaultCacheDirectoryIsWritable() {
		$propertyReflection = new \F3\FLOW3\Reflection\PropertyReflection($this->backend, 'cacheDirectory');
		$cacheDirectory = $propertyReflection->getValue($this->backend);
		$this->assertTrue(is_writable($cacheDirectory), 'The default cache directory "' . $cacheDirectory . '" is not writable.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Cache\Exception
	 */
	public function setCacheDirectoryThrowsExceptionOnNonWritableDirectory() {
		if (DIRECTORY_SEPARATOR == '\\') {
			$this->markTestSkipped('test not reliable in Windows environment');
		}
		$directoryName = '/sbin';

		$this->backend->setCacheDirectory($directoryName);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCacheDirectoryReturnsThePreviouslySetDirectory() {
		$environment = new \F3\FLOW3\Utility\Environment();
		$environment->setTemporaryDirectoryBase(FLOW3_PATH_DATA . 'Temporary/');

		$directory = $environment->getPathToTemporaryDirectory();
		$this->backend->setCacheDirectory($directory);
		$this->assertEquals($directory, $this->backend->getCacheDirectory(), 'getDirectory() did not return the expected value.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRejectsInvalidIdentifiers() {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$data = 'Some data';
		$this->backend->setCache($cache);

		foreach (array('', 'abc def', 'foo!', 'bar:', 'some/', 'bla*', 'one+', 'äöü', str_repeat('x', 251), 'x$', '\\a', 'b#', 'some&') as $entryIdentifier) {
			try {
				$this->backend->set($entryIdentifier, $data);
				$this->fail('set() did no reject the entry identifier "' . $entryIdentifier . '".');
			} catch (\InvalidArgumentException $exception) {
			}
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Cache\Exception\InvalidData
	 */
	public function setThrowsExceptionIfDataIsNotAString() {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);

		$data = array('Some data');
		$entryIdentifier = 'BackendFileTest';

		$this->backend->setCache($cache);

		$this->backend->set($entryIdentifier, $data);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setReallySavesToTheSpecifiedDirectory() {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$this->backend->setCache($cache);
		$this->backend->set($entryIdentifier, $data);

		$cacheDirectory = $this->backend->getCacheDirectory();
		$pattern = $cacheDirectory . 'Testing/Data/UnitTestCache/' . $entryIdentifierHash{0} . '/' . $entryIdentifierHash{1} . '/' . \F3\FLOW3\Cache\Backend\File::FILENAME_EXPIRYTIME_GLOB . \F3\FLOW3\Cache\Backend\File::SEPARATOR . $entryIdentifier;
		$filesFound = glob($pattern);
		$this->assertTrue(is_array($filesFound), 'filesFound was no array.');

		$retrievedData = file_get_contents(array_pop($filesFound));
		$this->assertEquals($data, $retrievedData, 'The original and the retrieved data don\'t match.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRemovesAnAlreadyExistingCacheEntryForTheSameIdentifier() {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data1 = 'some data' . microtime();
		$data2 = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemoveBeforeSetTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$this->backend->setCache($cache);
		$this->backend->set($entryIdentifier, $data1, array(), 500);
		$this->backend->set($entryIdentifier, $data2, array(), 200);

		$cacheDirectory = $this->backend->getCacheDirectory();
		$pattern = $cacheDirectory . 'Testing/Data/UnitTestCache/' . $entryIdentifierHash{0} . '/' . $entryIdentifierHash{1} . '/' . \F3\FLOW3\Cache\Backend\File::FILENAME_EXPIRYTIME_GLOB . \F3\FLOW3\Cache\Backend\File::SEPARATOR . $entryIdentifier ;
		$filesFound = glob($pattern);
		$this->assertEquals(1, count($filesFound), 'There was not exactly one cache entry.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setReallySavesSpecifiedTags() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue($cacheIdentifier));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$this->backend->setCache($cache);
		$tagsDirectory = $this->backend->getCacheDirectory() . 'Testing/Tags/';

		$this->backend->set($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));

		$this->assertTrue(is_dir($tagsDirectory . 'UnitTestTag%tag1'), 'Tag directory UnitTestTag%tag1 does not exist.');
		$this->assertTrue(is_dir($tagsDirectory . 'UnitTestTag%tag2'), 'Tag directory UnitTestTag%tag2 does not exist.');

		$filename = $tagsDirectory . 'UnitTestTag%tag1/' . $cacheIdentifier . \F3\FLOW3\Cache\Backend\File::SEPARATOR . $entryIdentifier;
		$this->assertTrue(file_exists($filename), 'File "' . $filename . '" does not exist.');

		$filename = $tagsDirectory . 'UnitTestTag%tag2/' . $cacheIdentifier . \F3\FLOW3\Cache\Backend\File::SEPARATOR . $entryIdentifier;
		$this->assertTrue(file_exists($filename), 'File "' . $filename . '" does not exist.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getReturnsContentOfTheCorrectCacheFile() {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($cache);

		$entryIdentifier = 'BackendFileTest';

		$data = 'some data' . microtime();
		$this->backend->set($entryIdentifier, $data, array(), 500);

		$data = 'some other data' . microtime();
		$this->backend->set($entryIdentifier, $data, array(), 100);

		$loadedData = $this->backend->get($entryIdentifier);
		$this->assertEquals($data, $loadedData, 'The original and the retrieved data don\'t match.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasReturnsTheCorrectResult() {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($cache);

		$entryIdentifier = 'BackendFileTest';

		$data = 'some data' . microtime();
		$this->backend->set($entryIdentifier, $data);

		$this->assertTrue($this->backend->has($entryIdentifier), 'has() did not return TRUE.');
		$this->assertFalse($this->backend->has($entryIdentifier . 'Not'), 'has() did not return FALSE.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 *
	 */
	public function removeReallyRemovesACacheEntry() {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemovalTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$cacheDirectory = $this->backend->getCacheDirectory();
		$this->backend->setCache($cache);

		$pattern = $cacheDirectory . 'Testing/Data/UnitTestCache/' . $entryIdentifierHash{0} . '/' . $entryIdentifierHash{1} . '/' . \F3\FLOW3\Cache\Backend\File::FILENAME_EXPIRYTIME_GLOB . \F3\FLOW3\Cache\Backend\File::SEPARATOR . $entryIdentifier;

		$this->backend->set($entryIdentifier, $data);
		$filesFound = glob($pattern);
		$this->assertTrue(is_array($filesFound) && count($filesFound) > 0, 'The cache entry does not exist.');

		$this->backend->remove($entryIdentifier);
		$filesFound = glob($pattern);
		$this->assertTrue(count($filesFound) == 0, 'The cache entry still exists.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 *
	 */
	public function collectGarbageReallyRemovesAnExpiredCacheEntry() {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemovalTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$cacheDirectory = $this->backend->getCacheDirectory();
		$this->backend->setCache($cache);

		$pattern = $cacheDirectory . 'Testing/Data/UnitTestCache/' . $entryIdentifierHash{0} . '/' . $entryIdentifierHash{1} . '/' . \F3\FLOW3\Cache\Backend\File::FILENAME_EXPIRYTIME_GLOB . \F3\FLOW3\Cache\Backend\File::SEPARATOR . $entryIdentifier;

		$this->backend->set($entryIdentifier, $data, array(), 1);
		$filesFound = glob($pattern);
		$this->assertTrue(is_array($filesFound) && count($filesFound) > 0, 'The cache entry does not exist.');

		sleep(3);

		$this->backend->collectGarbage($entryIdentifier);
		$filesFound = glob($pattern);
		$this->assertTrue(count($filesFound) == 0, 'The cache entry still exists.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 *
	 */
	public function collectGarbageReallyRemovesAllExpiredCacheEntries() {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemovalTest';

		$cacheDirectory = $this->backend->getCacheDirectory();
		$this->backend->setCache($cache);

		$pattern = $cacheDirectory . 'Testing/Data/UnitTestCache/*/*/' . \F3\FLOW3\Cache\Backend\File::FILENAME_EXPIRYTIME_GLOB . \F3\FLOW3\Cache\Backend\File::SEPARATOR . $entryIdentifier . '?';

		$this->backend->set($entryIdentifier . 'A', $data, array(), 1);
		$this->backend->set($entryIdentifier . 'B', $data, array(), 1);
		$this->backend->set($entryIdentifier . 'C', $data, array(), 1);
		$filesFound = glob($pattern);
		$this->assertTrue(is_array($filesFound) && count($filesFound) > 0, 'The cache entries do not exist.');

		sleep(3);

		$this->backend->collectGarbage();
		$filesFound = glob($pattern);
		$this->assertTrue(count($filesFound) == 0, 'The cache entries still exist.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 *
	 */
	public function removeReallyRemovesTagsOfRemovedEntry() {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';

		$this->backend->setCache($cache);

		$tagsDirectory = $this->backend->getCacheDirectory() . 'Testing/Tags/';

		$this->backend->set($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));
		$this->backend->remove($entryIdentifier);

		$this->assertTrue(!file_exists($tagsDirectory . 'UnitTestTag%tag1/' . $entryIdentifier), 'File "' . $tagsDirectory . 'UnitTestTag%tag1/' . $entryIdentifier . '" still exists.');
		$this->assertTrue(!file_exists($tagsDirectory . 'UnitTestTag%tag2/' . $entryIdentifier), 'File "' . $tagsDirectory . 'UnitTestTag%tag2/' . $entryIdentifier . '" still exists.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag() {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($cache);

		$data = 'some data' . microtime();
		$this->backend->set('BackendFileTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$this->backend->set('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$this->backend->set('BackendFileTest3', $data, array('UnitTestTag%test'));

		$expectedEntry = 'BackendFileTest2';

		$actualEntries = $this->backend->findIdentifiersByTag('UnitTestTag%special');
		$this->assertTrue(is_array($actualEntries), 'actualEntries is not an array.');

		$this->assertEquals($expectedEntry, array_pop($actualEntries));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushRemovesAllCacheEntriesAndRelatedTags() {
		$context = 'Testing';
		$data = 'some data' . microtime();

		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($cache);

		$tagsDirectory = $this->backend->getCacheDirectory() . $context . '/Tags/';
		$cacheDirectory = $this->backend->getCacheDirectory() . $context . '/Data/UnitTestCache/';

		$this->backend->set('BackendFileTest1', $data, array('UnitTestTag%test'));
		$this->backend->set('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$this->backend->set('BackendFileTest3', $data, array('UnitTestTag%test'));

		$this->backend->flush();

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
	 * @expectedException InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushByTagRejectsInvalidTags() {
		$this->backend->flushByTag('SomeInvalid\Tag');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($cache);

		$data = 'some data' . microtime();
		$this->backend->set('BackendFileTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$this->backend->set('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$this->backend->set('BackendFileTest3', $data, array('UnitTestTag%test'));

		$this->backend->flushByTag('UnitTestTag%special');

		$this->assertTrue($this->backend->has('BackendFileTest1'), 'BackendFileTest1');
		$this->assertFalse($this->backend->has('BackendFileTest2'), 'BackendFileTest2');
		$this->assertTrue($this->backend->has('BackendFileTest3'), 'BackendFileTest3');
	}


	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasReturnsTheCorrectResultForEntryWithExceededLifetime() {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($cache);

		$entryIdentifier = 'BackendFileTest';
		$data = 'some data' . microtime();
		$this->backend->set($entryIdentifier, $data);

		$expiredEntryIdentifier = 'ExpiredBackendFileTest';
		$expiredData = 'some old data' . microtime();
		$this->backend->set($expiredEntryIdentifier, $expiredData, array(), 1);

		sleep(3);

		$this->assertFalse($this->backend->has($expiredEntryIdentifier), 'has() did not return FALSE.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getReturnsFalseForEntryWithExceededLifetime() {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($cache);

		$entryIdentifier = 'BackendFileTest';
		$data = 'some data' . microtime();
		$this->backend->set($entryIdentifier, $data);

		$expiredEntryIdentifier = 'ExpiredBackendFileTest';
		$expiredData = 'some old data' . microtime();
		$this->backend->set($expiredEntryIdentifier, $expiredData, array(), 1);

		sleep(3);

		$this->assertEquals($data, $this->backend->get($entryIdentifier), 'The original and the retrieved data don\'t match.');
		$this->assertFalse($this->backend->get($expiredEntryIdentifier), 'The expired entry could be loaded.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findIdentifiersByTagReturnsEmptyArrayForEntryWithExceededLifetime() {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($cache);

		$this->backend->set('BackendFileTest', 'some data', array('UnitTestTag%special'), 1);

		sleep(3);

		$this->assertEquals(array(), $this->backend->findIdentifiersByTag('UnitTestTag%special'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setWithUnlimitedLifetimeWritesCorrectEntry() {
		$cache = $this->getMock('F3\FLOW3\Cache\AbstractCache', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$this->backend->setCache($cache);
		$this->backend->set($entryIdentifier, $data, array(), 0);

		$cacheDirectory = $this->backend->getCacheDirectory();
		$pattern = $cacheDirectory . 'Testing/Data/UnitTestCache/' . $entryIdentifierHash{0} . '/' . $entryIdentifierHash{1} . '/' . \F3\FLOW3\Cache\Backend\File::FILENAME_EXPIRYTIME_UNLIMITED . \F3\FLOW3\Cache\Backend\File::SEPARATOR . $entryIdentifier;
		$filesFound = glob($pattern);
		$this->assertTrue(is_array($filesFound), 'filesFound was no array.');

		$retrievedData = file_get_contents(array_pop($filesFound));
		$this->assertEquals($data, $retrievedData, 'The original and the retrieved data don\'t match.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function tearDown() {
		if (is_object($this->backend)) {
			$directory = $this->backend->getCacheDirectory() . 'Testing/Data/UnitTestCache';
			if (is_dir($directory)) \F3\FLOW3\Utility\Files::removeDirectoryRecursively($directory);

			$pattern = $this->backend->getCacheDirectory() . 'Testing/Tags/UnitTestTag%*/*';
			$filesFound = glob($pattern);
			if ($filesFound === FALSE || count($filesFound) == 0) return;

			foreach ($filesFound as $filename) {
				unlink($filename);
			}

			$pattern = $this->backend->getCacheDirectory() . 'Testing/Tags/UnitTestTag%*';
			$directoriesFound = glob($pattern);
			if ($directoriesFound === FALSE || count($directoriesFound) == 0) return;

			foreach ($directoriesFound as $directory) {
				rmdir($directory);
			}
		}
	}
}
?>