<?php
namespace TYPO3\Flow\Tests\Unit\Cache\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Core\ApplicationContext;

/**
 * Testcase for the PDO cache backend
 *
 */
class PdoBackendTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var string
	 */
	protected $fixtureFolder;

	/**
	 * @var string
	 */
	protected $fixtureDB;

	/**
	 * Set up this testcase
	 */
	public function setUp() {
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('The PHP PDO SQLite was not available');
		}
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Cache\Exception
	 */
	public function setThrowsExceptionIfNoFrontEndHasBeenSet() {
		$backend = new \TYPO3\Flow\Cache\Backend\PdoBackend(new ApplicationContext('Testing'));
		$backend->injectEnvironment($this->getMock('TYPO3\Flow\Utility\Environment', array(), array(), '', FALSE));
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
	}

	/**
	 * @test
	 */
	public function itIsPossibleToSetAndCheckExistenceInCache() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$this->assertTrue($backend->has($identifier));
	}

	/**
	 * @test
	 */
	public function itIsPossibleToSetAndGetEntry() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$fetchedData = $backend->get($identifier);
		$this->assertEquals($data, $fetchedData);
	}

	/**
	 * @test
	 */
	public function itIsPossibleToRemoveEntryFromCache() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$backend->remove($identifier);
		$this->assertFalse($backend->has($identifier));
	}

	/**
	 * @test
	 */
	public function itIsPossibleToOverwriteAnEntryInTheCache() {
		$backend = $this->setUpBackend();
		$data = 'Some data';
		$identifier = 'MyIdentifier';
		$backend->set($identifier, $data);
		$otherData = 'some other data';
		$backend->set($identifier, $otherData);
		$fetchedData = $backend->get($identifier);
		$this->assertEquals($otherData, $fetchedData);
	}

	/**
	 * @test
	 */
	public function findIdentifiersByTagFindsSetEntries() {
		$backend = $this->setUpBackend();

		$data = 'Some data';
		$entryIdentifier = 'MyIdentifier';
		$backend->set($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));

		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
		$this->assertEquals($entryIdentifier, $retrieved[0]);

		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
		$this->assertEquals($entryIdentifier, $retrieved[0]);
	}

	/**
	 * @test
	 */
	public function setRemovesTagsFromPreviousSet() {
		$backend = $this->setUpBackend();

		$data = 'Some data';
		$entryIdentifier = 'MyIdentifier';
		$backend->set($entryIdentifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));
		$backend->set($entryIdentifier, $data, array('UnitTestTag%tag3'));

		$retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
		$this->assertEquals(array(), $retrieved);
	}

	/**
	 * @test
	 */
	public function hasReturnsFalseIfTheEntryDoesntExist() {
		$backend = $this->setUpBackend();
		$identifier = 'NonExistingIdentifier';
		$this->assertFalse($backend->has($identifier));
	}

	/**
	 * @test
	 */
	public function removeReturnsFalseIfTheEntryDoesntExist() {
		$backend = $this->setUpBackend();
		$identifier = 'NonExistingIdentifier';
		$this->assertFalse($backend->remove($identifier));
	}

	/**
	 * @test
	 */
	public function flushByTagRemovesCacheEntriesWithSpecifiedTag() {
		$backend = $this->setUpBackend();

		$data = 'some data' . microtime();
		$backend->set('PdoBackendTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
		$backend->set('PdoBackendTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
		$backend->set('PdoBackendTest3', $data, array('UnitTestTag%test'));

		$backend->flushByTag('UnitTestTag%special');

		$this->assertTrue($backend->has('PdoBackendTest1'), 'PdoBackendTest1');
		$this->assertFalse($backend->has('PdoBackendTest2'), 'PdoBackendTest2');
		$this->assertTrue($backend->has('PdoBackendTest3'), 'PdoBackendTest3');
	}

	/**
	 * @test
	 */
	public function flushRemovesAllCacheEntries() {
		$backend = $this->setUpBackend();

		$data = 'some data' . microtime();
		$backend->set('PdoBackendTest1', $data);
		$backend->set('PdoBackendTest2', $data);
		$backend->set('PdoBackendTest3', $data);

		$backend->flush();

		$this->assertFalse($backend->has('PdoBackendTest1'), 'PdoBackendTest1');
		$this->assertFalse($backend->has('PdoBackendTest2'), 'PdoBackendTest2');
		$this->assertFalse($backend->has('PdoBackendTest3'), 'PdoBackendTest3');
	}

	/**
	 * @test
	 */
	public function flushRemovesOnlyOwnEntries() {
		$thisCache = $this->getMock('TYPO3\Flow\Cache\Frontend\FrontendInterface', array(), array(), '', FALSE);
		$thisCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thisCache'));
		$thisBackend = $this->setUpBackend();
		$thisBackend->setCache($thisCache);

		$thatCache = $this->getMock('TYPO3\Flow\Cache\Frontend\FrontendInterface', array(), array(), '', FALSE);
		$thatCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thatCache'));
		$thatBackend = $this->setUpBackend();
		$thatBackend->setCache($thatCache);

		$thisBackend->set('thisEntry', 'Hello');
		$thatBackend->set('thatEntry', 'World!');
		$thatBackend->flush();

		$this->assertEquals('Hello', $thisBackend->get('thisEntry'));
		$this->assertFalse($thatBackend->has('thatEntry'));
	}

	/**
	 * Sets up the APC backend used for testing
	 *
	 * @return \TYPO3\Flow\Cache\Backend\PdoBackend
	 */
	protected function setUpBackend() {
		$mockEnvironment = $this->getMock('TYPO3\Flow\Utility\Environment', array(), array(), '', FALSE);

		$mockCache = $this->getMock('TYPO3\Flow\Cache\Frontend\FrontendInterface', array(), array(), '', FALSE);
		$mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('TestCache'));

		$backend = new \TYPO3\Flow\Cache\Backend\PdoBackend(new ApplicationContext('Testing'));
		$backend->injectEnvironment($mockEnvironment);
		$backend->setCache($mockCache);
		$backend->setDataSourceName('sqlite::memory:');
		$backend->initializeObject();

		return $backend;
	}

}
?>