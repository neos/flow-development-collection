<?php
namespace TYPO3\Flow\Tests\Unit\Cache\Backend;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Cache\Backend\ApcBackend;
use TYPO3\Flow\Core\ApplicationContext;

/**
 * Testcase for the APC cache backend
 *
 * @requires extension apc
 */
class ApcBackendTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Utility\Environment
     */
    protected $mockEnvironment;

    /**
     * Sets up this testcase
     *
     * @return void
     */
    public function setUp()
    {
        $this->markTestSkipped('Disabling ALL apc tests for now as they are so unreliable');
        if (ini_get('apc.enabled') == 0 || ini_get('apc.enable_cli') == 0) {
            $this->markTestSkipped('APC is disabled (for CLI).');
        }
        if (ini_get('apc.slam_defense') == 1) {
            $this->markTestSkipped('This testcase can only be executed with apc.slam_defense = Off');
        }
        $this->mockEnvironment = $this->getMock(\TYPO3\Flow\Utility\Environment::class, array(), array(), '', false);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Cache\Exception
     */
    public function setThrowsExceptionIfNoFrontEndHasBeenSet()
    {
        $backend = new ApcBackend(new ApplicationContext('Testing'));
        $backend->injectEnvironment($this->mockEnvironment);
        $data = 'Some data';
        $identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), true));
        $backend->set($identifier, $data);
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndCheckExistenceInCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), true));
        $backend->set($identifier, $data);
        $inCache = $backend->has($identifier);
        $this->assertTrue($inCache, 'APC backend failed to set and check entry');
    }

    /**
     * @ test
     */
    public function itIsPossibleToSetAndGetEntry()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), true));
        $backend->set($identifier, $data);
        $fetchedData = $backend->get($identifier);
        $this->assertEquals($data, $fetchedData, 'APC backend failed to set and retrieve data');
    }

    /**
     * @test
     */
    public function itIsPossibleToRemoveEntryFromCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), true));
        $backend->set($identifier, $data);
        $backend->remove($identifier);
        $inCache = $backend->has($identifier);
        $this->assertFalse($inCache, 'Failed to set and remove data from APC backend');
    }

    /**
     * @test
     */
    public function itIsPossibleToOverwriteAnEntryInTheCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), true));
        $backend->set($identifier, $data);
        $otherData = 'some other data';
        $backend->set($identifier, $otherData);
        $fetchedData = $backend->get($identifier);
        $this->assertEquals($otherData, $fetchedData, 'APC backend failed to overwrite and retrieve data');
    }

    /**
     * @test
     */
    public function findIdentifiersByTagFindsSetEntries()
    {
        $backend = $this->setUpBackend();

        $data = 'Some data';
        $identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), true));
        $backend->set($identifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tag2'));

        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
        $this->assertEquals($identifier, $retrieved[0], 'Could not retrieve expected entry by tag.');

        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
        $this->assertEquals($identifier, $retrieved[0], 'Could not retrieve expected entry by tag.');
    }

    /**
     * @test
     */
    public function setRemovesTagsFromPreviousSet()
    {
        $backend = $this->setUpBackend();

        $data = 'Some data';
        $identifier = 'MyIdentifier' . md5(uniqid(mt_rand(), true));
        $backend->set($identifier, $data, array('UnitTestTag%tag1', 'UnitTestTag%tagX'));
        $backend->set($identifier, $data, array('UnitTestTag%tag3'));

        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tagX');
        $this->assertEquals(array(), $retrieved, 'Found entry which should no longer exist.');
    }

    /**
     * @test
     */
    public function hasReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = 'NonExistingIdentifier' . md5(uniqid(mt_rand(), true));
        $inCache = $backend->has($identifier);
        $this->assertFalse($inCache, '"has" did not return false when checking on non existing identifier');
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = 'NonExistingIdentifier' . md5(uniqid(mt_rand(), true));
        $inCache = $backend->remove($identifier);
        $this->assertFalse($inCache, '"remove" did not return false when checking on non existing identifier');
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag()
    {
        $backend = $this->setUpBackend();

        $data = 'some data' . microtime();
        $backend->set('BackendAPCTest1', $data, array('UnitTestTag%test', 'UnitTestTag%boring'));
        $backend->set('BackendAPCTest2', $data, array('UnitTestTag%test', 'UnitTestTag%special'));
        $backend->set('BackendAPCTest3', $data, array('UnitTestTag%test'));

        $backend->flushByTag('UnitTestTag%special');

        $this->assertTrue($backend->has('BackendAPCTest1'), 'BackendAPCTest1');
        $this->assertFalse($backend->has('BackendAPCTest2'), 'BackendAPCTest2');
        $this->assertTrue($backend->has('BackendAPCTest3'), 'BackendAPCTest3');
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
        $backend = $this->setUpBackend();

        $data = 'some data' . microtime();
        $backend->set('BackendAPCTest1', $data);
        $backend->set('BackendAPCTest2', $data);
        $backend->set('BackendAPCTest3', $data);

        $backend->flush();

        $this->assertFalse($backend->has('BackendAPCTest1'), 'BackendAPCTest1');
        $this->assertFalse($backend->has('BackendAPCTest2'), 'BackendAPCTest2');
        $this->assertFalse($backend->has('BackendAPCTest3'), 'BackendAPCTest3');
    }

    /**
     * @test
     */
    public function flushRemovesOnlyOwnEntries()
    {
        $thisCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\FrontendInterface::class, array(), array(), '', false);
        $thisCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thisCache'));
        $thisBackend = new ApcBackend(new ApplicationContext('Testing'));
        $thisBackend->injectEnvironment($this->mockEnvironment);
        $thisBackend->setCache($thisCache);

        $thatCache = $this->getMock(\TYPO3\Flow\Cache\Frontend\FrontendInterface::class, array(), array(), '', false);
        $thatCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thatCache'));
        $thatBackend = new ApcBackend(new ApplicationContext('Testing'));
        $thatBackend->injectEnvironment($this->mockEnvironment);
        $thatBackend->setCache($thatCache);

        $thisBackend->set('thisEntry', 'Hello');
        $thatBackend->set('thatEntry', 'World!');
        $thatBackend->flush();

        $this->assertEquals('Hello', $thisBackend->get('thisEntry'));
        $this->assertFalse($thatBackend->has('thatEntry'));
    }

    /**
     * Check if we can store ~5 MB of data, this gives some headroom.
     *
     * @test
     */
    public function largeDataIsStored()
    {
        $backend = $this->setUpBackend();

        $data = str_repeat('abcde', 1024 * 1024);
        $identifier = 'tooLargeData' . md5(uniqid(mt_rand(), true));
        $backend->set($identifier, $data);

        $this->assertTrue($backend->has($identifier));
        $this->assertEquals($backend->get($identifier), $data);
    }

    /**
     * @test
     */
    public function backendAllowsForIteratingOverEntries()
    {
        $backend = $this->setUpBackend();

        $cache = new \TYPO3\Flow\Cache\Frontend\VariableFrontend('UnitTestCache', $backend);
        $backend->setCache($cache);

        for ($i = 0; $i < 100; $i++) {
            $entryIdentifier = sprintf('entry-%s', $i);
            $data = 'some data ' . $i;
            $cache->set($entryIdentifier, $data);
        }

        $entries = array();
        foreach ($cache->getIterator() as $entryIdentifier => $data) {
            $entries[$entryIdentifier] = $data;
        }
        natsort($entries);
        $i = 0;
        foreach ($entries as $entryIdentifier => $data) {
            $this->assertEquals(sprintf('entry-%s', $i), $entryIdentifier);
            $this->assertEquals('some data ' . $i, $data);
            $i++;
        }
        $this->assertEquals(100, $i);
    }

    /**
     * Sets up the APC backend used for testing
     *
     * @return ApcBackend
     */
    protected function setUpBackend()
    {
        $cache = $this->getMock(\TYPO3\Flow\Cache\Frontend\FrontendInterface::class, array(), array(), '', false);
        $backend = new ApcBackend(new ApplicationContext('Testing'));
        $backend->injectEnvironment($this->mockEnvironment);
        $backend->setCache($cache);
        return $backend;
    }
}
