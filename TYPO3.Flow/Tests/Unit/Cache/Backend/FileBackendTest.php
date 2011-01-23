<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Cache\Backend;

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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FileBackendTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \F3\FLOW3\Cache\Backend\FileBackendBackend If set, the tearDown() method will clean up the cache subdirectory used by this unit test.
	 */
	protected $backend;

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Cache\Exception
	 */
	public function setCacheThrowsExceptionOnNonWritableDirectory() {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('http://localhost/'));

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('dummy'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);

		$backend->setCache($mockCache);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCacheDirectoryReturnsTheCurrentCacheDirectory() {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('SomeCache'));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

			// We need to create the directory here because vfs doesn't support touch() which is used by
			// createDirectoryRecursively() in the setCache method.
		mkdir ('vfs://Foo/Cache');

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('dummy'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);

		$this->assertEquals('vfs://Foo/Cache/SomeCache/', $backend->getCacheDirectory());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Cache\Exception\InvalidDataException
	 */
	public function setThrowsExceptionIfDataIsNotAString() {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('dummy'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);
		$backend->set('SomeIdentifier', array('not a string'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setReallySavesToTheSpecifiedDirectory() {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';
		$pathAndFilename = 'vfs://Foo/Cache/UnitTestCache/' . '/' . $entryIdentifier;

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('dummy'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);

		$backend->set($entryIdentifier, $data);

		$this->assertFileExists($pathAndFilename);
		$retrievedData = file_get_contents($pathAndFilename, NULL, NULL, 0, strlen($data));
		$this->assertEquals($data, $retrievedData);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setOverwritesAnAlreadyExistingCacheEntryForTheSameIdentifier() {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

		$data1 = 'some data' . microtime();
		$data2 = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemoveBeforeSetTest';

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('dummy'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);

		$backend->set($entryIdentifier, $data1, array(), 500);
		$backend->set($entryIdentifier, $data2, array(), 200);

		$pathAndFilename = 'vfs://Foo/Cache/UnitTestCache/' . $entryIdentifier;
		$this->assertFileExists($pathAndFilename);
		$retrievedData = file_get_contents($pathAndFilename, NULL, NULL, 0, strlen($data2));
		$this->assertEquals($data2, $retrievedData);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setAlsoSavesSpecifiedTags() {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileRemoveBeforeSetTest';

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('dummy'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);

		$backend->set($entryIdentifier, $data, array('Tag1', 'Tag2'));

		$pathAndFilename = 'vfs://Foo/Cache/UnitTestCache/' . $entryIdentifier;
		$this->assertFileExists($pathAndFilename);
		$retrievedData = file_get_contents($pathAndFilename, NULL, NULL, (strlen($data) + \F3\FLOW3\Cache\Backend\FileBackend::EXPIRYTIME_LENGTH), 9);
		$this->assertEquals('Tag1 Tag2', $retrievedData);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Cache\Exception
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setThrowsExceptionIfCachePathLengthExceedsMaximumPathLength() {
		$cacheIdentifier = 'UnitTestCache';
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue($cacheIdentifier));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(5));
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

		$entryIdentifier = 'BackendFileTest';

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('setTag'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);

		$backend->set($entryIdentifier, 'cache data');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getReturnsContentOfTheCorrectCacheFile() {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('setTag'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);

		$entryIdentifier = 'BackendFileTest';

		$data = 'some data' . microtime();
		$backend->set($entryIdentifier, $data, array(), 500);

		$data = 'some other data' . microtime();
		$backend->set($entryIdentifier, $data, array(), 100);

		$loadedData = $backend->get($entryIdentifier);
		$this->assertEquals($data, $loadedData);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getReturnsFalseForExpiredEntries() {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('isCacheFileExpired'), array(), '', FALSE);
		$backend->expects($this->once())->method('isCacheFileExpired')->with('vfs://Foo/Cache/UnitTestCache/ExpiredEntry')->will($this->returnValue(TRUE));
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);

		$this->assertFalse($backend->get('ExpiredEntry'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasReturnsTrueIfAnEntryExists() {
		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('dummy'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);

		$entryIdentifier = 'BackendFileTest';

		$data = 'some data' . microtime();
		$backend->set($entryIdentifier, $data);

		$this->assertTrue($backend->has($entryIdentifier), 'has() did not return TRUE.');
		$this->assertFalse($backend->has($entryIdentifier . 'Not'), 'has() did not return FALSE.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasReturnsFalseForExpiredEntries() {
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('isCacheFileExpired'), array(), '', FALSE);
		$backend->expects($this->exactly(2))->method('isCacheFileExpired')->will($this->onConsecutiveCalls(TRUE, FALSE));

		$this->assertFalse($backend->has('foo'));
		$this->assertTrue($backend->has('bar'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 *
	 */
	public function removeReallyRemovesACacheEntry() {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

		$data = 'some data' . microtime();
		$entryIdentifier = 'BackendFileTest';
		$pathAndFilename = 'vfs://Foo/Cache/UnitTestCache/' . '/' . $entryIdentifier;

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('dummy'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);

		$backend->set($entryIdentifier, $data);
		$this->assertFileExists($pathAndFilename);

		$backend->remove($entryIdentifier);
		$this->assertFileNotExists($pathAndFilename);
	}

	/**
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function invalidEntryIdentifiers() {
		return array(
			'trailing slash' => array('/myIdentifer'),
			'trailing dot and slash' => array('./myIdentifer'),
			'trailing two dots and slash' => array('../myIdentifier'),
			'trailing with multiple dots and slashes' => array('.././../myIdentifier'),
			'slash in middle part' => array('my/Identifier'),
			'dot and slash in middle part' => array('my./Identifier'),
			'two dots and slash in middle part' => array('my../Identifier'),
			'multiple dots and slashes in middle part' => array('my.././../Identifier'),
			'pending slash' => array('myIdentifier/'),
			'pending dot and slash' => array('myIdentifier./'),
			'pending dots and slash' => array('myIdentifier../'),
			'pending multiple dots and slashes' => array('myIdentifier.././../'),
		);
	}

	/**
	 * @test
	 * @dataProvider invalidEntryIdentifiers
	 * @expectedException InvalidArgumentException
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function setThrowsExceptionForInvalidIdentifier($identifier) {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('dummy'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);

		$backend->set($identifier, 'cache data', array());
	}

	/**
	 * @test
	 * @dataProvider invalidEntryIdentifiers
	 * @expectedException InvalidArgumentException
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function getThrowsExceptionForInvalidIdentifier($identifier) {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('isCacheFileExpired'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);

		$backend->get($identifier);
	}

	/**
	 * @test
	 * @dataProvider invalidEntryIdentifiers
	 * @expectedException InvalidArgumentException
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function hasThrowsExceptionForInvalidIdentifier($identifier) {
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('dummy'), array(), '', FALSE);

		$backend->has($identifier);
	}

	/**
	 * @test
	 * @dataProvider invalidEntryIdentifiers
	 * @expectedException InvalidArgumentException
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function removeThrowsExceptionForInvalidIdentifier($identifier) {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('dummy'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);

		$backend->remove($identifier);
	}

	/**
	 * @test
	 * @dataProvider invalidEntryIdentifiers
	 * @expectedException InvalidArgumentException
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function requireOnceThrowsExceptionForInvalidIdentifier($identifier) {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('dummy'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);

		$backend->requireOnce($identifier);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findIdentifiersByTagFindsCacheEntriesWithSpecifiedTag() {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('dummy'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);

		$data = 'some data' . microtime();
		$backend->set('BackendFileTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$backend->set('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$backend->set('BackendFileTest3', $data, array('UnitTestTag%test'));

		$expectedEntry = 'BackendFileTest2';

		$actualEntries = $backend->findIdentifiersByTag('UnitTestTag%special');
		$this->assertInternalType('array', $actualEntries);

		$this->assertEquals($expectedEntry, array_pop($actualEntries));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findIdentifiersByTagReturnsEmptyArrayForExpiredEntries() {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('dummy'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);

		$data = 'some data';
		$backend->set('BackendFileTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$backend->set('BackendFileTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'), -100);
		$backend->set('BackendFileTest3', $data, array('UnitTestTag%test'));

		$this->assertSame(array(), $backend->findIdentifiersByTag('UnitTestTag%special'));
		$this->assertSame(array('BackendFileTest1', 'BackendFileTest3'), $backend->findIdentifiersByTag('UnitTestTag%test'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushRemovesAllCacheEntries() {
		$mockCache = $this->getMock('F3\FLOW3\Cache\Frontend\AbstractFrontend', array(), array(), '', FALSE);
		$mockCache->expects($this->atLeastOnce())->method('getIdentifier')->will($this->returnValue('UnitTestCache'));

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getMaximumPathLength')->will($this->returnValue(255));
		$mockEnvironment->expects($this->any())->method('getPathToTemporaryDirectory')->will($this->returnValue('vfs://Foo/'));

		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('dummy'), array(), '', FALSE);
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);

		$data = 'some data';
		$backend->set('BackendFileTest1', $data);
		$backend->set('BackendFileTest2', $data);

		$this->assertFileExists('vfs://Foo/Cache/UnitTestCache/BackendFileTest1');
		$this->assertFileExists('vfs://Foo/Cache/UnitTestCache/BackendFileTest2');

		$backend->flush();

		$this->assertFileNotExists('vfs://Foo/Cache/UnitTestCache/BackendFileTest1');
		$this->assertFileNotExists('vfs://Foo/Cache/UnitTestCache/BackendFileTest2');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		$backend = $this->getMock('F3\FLOW3\Cache\Backend\FileBackend', array('findIdentifiersByTag', 'remove'), array(), '', FALSE);

		$backend->expects($this->once())->method('findIdentifiersByTag')->with('UnitTestTag%special')->will($this->returnValue(array('foo', 'bar', 'baz')));
		$backend->expects($this->at(1))->method('remove')->with('foo');
		$backend->expects($this->at(2))->method('remove')->with('bar');
		$backend->expects($this->at(3))->method('remove')->with('baz');

		$backend->flushByTag('UnitTestTag%special');
	}
}
?>