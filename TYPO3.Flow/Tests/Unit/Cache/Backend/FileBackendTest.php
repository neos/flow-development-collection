<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Cache\Backend;

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
 * Testcase for the cache to file backend
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FileBackendTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Cache\Backend\FileBackendBackend If set, the tearDown() method will clean up the cache subdirectory used by this unit test.
	 */
	protected $backend;

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * Sets up this testcase
	 *
	 * @return void
	 */
	public function setUp() {
		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$this->environment = $this->getMock('F3\FLOW3\Utility\Environment');
		$this->environment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue(FLOW3_PATH_DATA . 'Temporary/'));
		$this->environment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(PHP_MAXPATHLEN));

		$this->backend = new \F3\FLOW3\Cache\Backend\FileBackend('Testing');
		$this->backend->injectEnvironment($this->environment);
		$this->backend->injectSystemLogger($mockSystemLogger);
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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getCacheDirectoryReturnsThePreviouslySetDirectory() {
		$directory = FLOW3_PATH_DATA . 'Temporary/';
		$this->backend->setCacheDirectory($directory);
		$this->assertEquals($directory, $this->backend->getCacheDirectory(), 'getDirectory() did not return the expected value.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Cache\Exception\InvalidData
	 */
	public function setThrowsExceptionIfDataIsNotAString() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);

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
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$this->backend->setCache($cache);
		$this->backend->set($entryIdentifier, $data);

		$cacheDirectory = $this->backend->getCacheDirectory();
		$pathAndFilename = $cacheDirectory . 'Data/UnitTestCache/' . $entryIdentifierHash[0] . '/' . $entryIdentifierHash[1] . '/' . $entryIdentifier;
		$this->assertTrue(file_exists($pathAndFilename), 'File does not exist.');
		$retrievedData = file_get_contents($pathAndFilename, NULL, NULL, \F3\FLOW3\Cache\Backend\FileBackend::EXPIRYTIME_LENGTH);
		$this->assertEquals($data, $retrievedData, 'The original and the retrieved data don\'t match.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setOverwritesAnAlreadyExistingCacheEntryForTheSameIdentifier() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data1 = 'some data' . microtime();
		$data2 = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemoveBeforeSetTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$this->backend->setCache($cache);
		$this->backend->set($entryIdentifier, $data1, array(), 500);
		$this->backend->set($entryIdentifier, $data2, array(), 200);

		$cacheDirectory = $this->backend->getCacheDirectory();
		$pathAndFilename = $cacheDirectory . 'Data/UnitTestCache/' . $entryIdentifierHash[0] . '/' . $entryIdentifierHash[1] . '/' . $entryIdentifier;
		$this->assertTrue(file_exists($pathAndFilename), 'File does not exist.');
		$retrievedData = file_get_contents($pathAndFilename, NULL, NULL, \F3\FLOW3\Cache\Backend\FileBackend::EXPIRYTIME_LENGTH);
		$this->assertEquals($data2, $retrievedData);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setReallySavesSpecifiedTags() {
		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue($cacheIdentifier));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';

		$this->backend->setCache($cache);
		$tagsDirectory = $this->backend->getCacheDirectory() . 'Tags/';

		$this->backend->set($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));

		$this->assertTrue(is_dir($tagsDirectory . 'UnitTestTag%tag1'), 'Tag directory UnitTestTag%tag1 does not exist.');
		$this->assertTrue(is_dir($tagsDirectory . 'UnitTestTag%tag2'), 'Tag directory UnitTestTag%tag2 does not exist.');

		$filename = $tagsDirectory . 'UnitTestTag%tag1/' . $cacheIdentifier . \F3\FLOW3\Cache\Backend\FileBackend::SEPARATOR . $entryIdentifier;
		$this->assertTrue(file_exists($filename), 'File "' . $filename . '" does not exist.');

		$filename = $tagsDirectory . 'UnitTestTag%tag2/' . $cacheIdentifier . \F3\FLOW3\Cache\Backend\FileBackend::SEPARATOR . $entryIdentifier;
		$this->assertTrue(file_exists($filename), 'File "' . $filename . '" does not exist.');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Cache\Exception
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setThrowsExceptionIfCachePathLengthExceedsMaximumPathLength() {
		$backend = new \F3\FLOW3\Cache\Backend\FileBackend('Testing');

		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue($cacheIdentifier));
		$backend->setCache($cache);

		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');
		$backend->injectSystemLogger($mockSystemLogger);

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment');
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue(FLOW3_PATH_DATA . 'Temporary/'));
		$mockEnvironment->expects($this->atLeastOnce())->method('getMaximumPathLength')->will($this->returnValue(3));
		$backend->injectEnvironment($mockEnvironment);
		$backend->initializeObject();

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';

		$backend->set($entryIdentifier, $data);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Cache\Exception
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setTagThrowsExceptionIfTagPathLengthExceedsMaximumPathLength() {
		$backend = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Cache\Backend\FileBackend'), array('dummy'), array('Testing'));

		$cacheIdentifier = 'UnitTestCache';
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue($cacheIdentifier));
		$backend->setCache($cache);

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment');
		$mockEnvironment->expects($this->atLeastOnce())->method('getMaximumPathLength')->will($this->returnValue(3));
		$backend->injectEnvironment($mockEnvironment);
		$backend->_set('cacheDirectory', FLOW3_PATH_DATA . 'Temporary/');
		$backend->initializeObject();

		$backend->_call('setTag', 'someIdentifier', 'someTag');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getReturnsContentOfTheCorrectCacheFile() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
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
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
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
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemovalTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$cacheDirectory = $this->backend->getCacheDirectory();
		$this->backend->setCache($cache);

		$pathAndFilename = $cacheDirectory . 'Data/UnitTestCache/' . $entryIdentifierHash[0] . '/' . $entryIdentifierHash[1] . '/' . $entryIdentifier;

		$this->backend->set($entryIdentifier, $data);
		$this->assertTrue(file_exists($pathAndFilename), 'The cache entry does not exist.');

		$this->backend->remove($entryIdentifier);
		$this->assertFalse(file_exists($pathAndFilename), 'The cache entry still exists.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 *
	 */
	public function collectGarbageReallyRemovesAnExpiredCacheEntry() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemovalTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$cacheDirectory = $this->backend->getCacheDirectory();
		$this->backend->setCache($cache);

		$pathAndFilename = $cacheDirectory . 'Data/UnitTestCache/' . $entryIdentifierHash[0] . '/' . $entryIdentifierHash[1] . '/' . $entryIdentifier;

		$this->backend->set($entryIdentifier, $data, array(), 1);
		$this->assertTrue(file_exists($pathAndFilename), 'The cache entry does not exist.');

		sleep(2);

		$this->backend->collectGarbage();
		$this->assertFalse(file_exists($pathAndFilename), 'The cache entry still exists.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 *
	 */
	public function collectGarbageReallyRemovesAllExpiredCacheEntries() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemovalTest';

		$cacheDirectory = $this->backend->getCacheDirectory();
		$this->backend->setCache($cache);

		$pattern = $cacheDirectory . 'Data/UnitTestCache/*/*/' . $entryIdentifier . '?';

		$this->backend->set($entryIdentifier . 'A', $data, array(), 1);
		$this->backend->set($entryIdentifier . 'B', $data, array(), 1);
		$this->backend->set($entryIdentifier . 'C', $data, array(), 1);
		$filesFound = glob($pattern);
		$this->assertTrue(is_array($filesFound) && count($filesFound) > 0, 'The cache entries do not exist.');

		sleep(2);

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
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';

		$this->backend->setCache($cache);

		$tagsDirectory = $this->backend->getCacheDirectory() . 'Tags/';

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
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
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

		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
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
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($cache);

		$entryIdentifier = 'BackendFileTest';
		$data = 'some data' . microtime();
		$this->backend->set($entryIdentifier, $data);

		$expiredEntryIdentifier = 'ExpiredBackendFileTest';
		$expiredData = 'some old data' . microtime();
		$this->backend->set($expiredEntryIdentifier, $expiredData, array(), 1);

		sleep(2);

		$this->assertFalse($this->backend->has($expiredEntryIdentifier), 'has() did not return FALSE.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getReturnsFalseForEntryWithExceededLifetime() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($cache);

		$entryIdentifier = 'BackendFileTest';
		$data = 'some data' . microtime();
		$this->backend->set($entryIdentifier, $data);

		$expiredEntryIdentifier = 'ExpiredBackendFileTest';
		$expiredData = 'some old data' . microtime();
		$this->backend->set($expiredEntryIdentifier, $expiredData, array(), 1);

		sleep(2);

		$this->assertEquals($data, $this->backend->get($entryIdentifier), 'The original and the retrieved data don\'t match.');
		$this->assertFalse($this->backend->get($expiredEntryIdentifier), 'The expired entry could be loaded.');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findIdentifiersByTagReturnsEmptyArrayForEntryWithExceededLifetime() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$this->backend->setCache($cache);

		$this->backend->set('BackendFileTest', 'some data', array('UnitTestTag%special'), 1);

		sleep(2);

		$this->assertEquals(array(), $this->backend->findIdentifiersByTag('UnitTestTag%special'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setWithUnlimitedLifetimeWritesCorrectEntry() {
		$cache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array('getIdentifier', 'set', 'get', 'getByTag', 'has', 'remove'), array(), '', FALSE);
		$cache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';
		$entryIdentifierHash = sha1($entryIdentifier);

		$this->backend->setCache($cache);
		$this->backend->set($entryIdentifier, $data, array(), 0);

		$cacheDirectory = $this->backend->getCacheDirectory();
		$pathAndFilename = $cacheDirectory . 'Data/UnitTestCache/' . $entryIdentifierHash[0] . '/' . $entryIdentifierHash[1] . '/' . $entryIdentifier;
		$this->assertTrue(file_exists($pathAndFilename), 'File not found.');

		$retrievedData = file_get_contents($pathAndFilename, NULL, NULL, \F3\FLOW3\Cache\Backend\FileBackend::EXPIRYTIME_LENGTH);
		$this->assertEquals($data, $retrievedData, 'The original and the retrieved data don\'t match.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function tearDown() {
		if (is_object($this->backend)) {
			$directory = $this->backend->getCacheDirectory() . 'Data/UnitTestCache';
			if (is_dir($directory)) \F3\FLOW3\Utility\Files::removeDirectoryRecursively($directory);

			$pattern = $this->backend->getCacheDirectory() . 'Tags/UnitTestTag%*/*';
			$filesFound = glob($pattern);
			if ($filesFound === FALSE || count($filesFound) == 0) return;

			foreach ($filesFound as $filename) {
				unlink($filename);
			}

			$pattern = $this->backend->getCacheDirectory() . 'Tags/UnitTestTag%*';
			$directoriesFound = glob($pattern);
			if ($directoriesFound === FALSE || count($directoriesFound) == 0) return;

			foreach ($directoriesFound as $directory) {
				rmdir($directory);
			}
		}
	}
}
?>