<?php
namespace Neos\Cache\Tests\Unit\Backend;

include_once(__DIR__ . '/../../BaseTestCase.php');

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\PdoBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Tests\BaseTestCase;

/**
 * Testcase for the PDO cache backend
 *
 * @requires extension pdo_sqlite
 */
class PdoBackendTest extends BaseTestCase
{
    /**
     * @var string
     */
    protected $fixtureFolder;

    /**
     * @var string
     */
    protected $fixtureDB;

    /**
     * @test
     * @expectedException \Neos\Cache\Exception
     */
    public function setThrowsExceptionIfNoFrontEndHasBeenSet()
    {
        $backend = new PdoBackend(new EnvironmentConfiguration('SomeApplication Testing', '/some/path', PHP_MAXPATHLEN));
        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndCheckExistenceInCache()
    {
        $backend = $this->setUpBackend();
        $data = 'Some data';
        $identifier = 'MyIdentifier';
        $backend->set($identifier, $data);
        $this->assertTrue($backend->has($identifier));
    }

    /**
     * @test
     */
    public function itIsPossibleToSetAndGetEntry()
    {
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
    public function itIsPossibleToRemoveEntryFromCache()
    {
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
    public function itIsPossibleToOverwriteAnEntryInTheCache()
    {
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
    public function findIdentifiersByTagFindsSetEntries()
    {
        $backend = $this->setUpBackend();

        $data = 'Some data';
        $entryIdentifier = 'MyIdentifier';
        $backend->set($entryIdentifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tag2']);

        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag1');
        $this->assertEquals($entryIdentifier, $retrieved[0]);

        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
        $this->assertEquals($entryIdentifier, $retrieved[0]);
    }

    /**
     * @test
     */
    public function setRemovesTagsFromPreviousSet()
    {
        $backend = $this->setUpBackend();

        $data = 'Some data';
        $entryIdentifier = 'MyIdentifier';
        $backend->set($entryIdentifier, $data, ['UnitTestTag%tag1', 'UnitTestTag%tag2']);
        $backend->set($entryIdentifier, $data, ['UnitTestTag%tag3']);

        $retrieved = $backend->findIdentifiersByTag('UnitTestTag%tag2');
        $this->assertEquals([], $retrieved);
    }

    /**
     * @test
     */
    public function hasReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = 'NonExistingIdentifier';
        $this->assertFalse($backend->has($identifier));
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfTheEntryDoesntExist()
    {
        $backend = $this->setUpBackend();
        $identifier = 'NonExistingIdentifier';
        $this->assertFalse($backend->remove($identifier));
    }

    /**
     * @test
     */
    public function flushByTagRemovesCacheEntriesWithSpecifiedTag()
    {
        $backend = $this->setUpBackend();

        $data = 'some data' . microtime();
        $backend->set('PdoBackendTest1', $data, ['UnitTestTag%test', 'UnitTestTag%boring']);
        $backend->set('PdoBackendTest2', $data, ['UnitTestTag%test', 'UnitTestTag%special']);
        $backend->set('PdoBackendTest3', $data, ['UnitTestTag%test']);

        $backend->flushByTag('UnitTestTag%special');

        $this->assertTrue($backend->has('PdoBackendTest1'), 'PdoBackendTest1');
        $this->assertFalse($backend->has('PdoBackendTest2'), 'PdoBackendTest2');
        $this->assertTrue($backend->has('PdoBackendTest3'), 'PdoBackendTest3');
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
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
    public function flushRemovesOnlyOwnEntries()
    {
        $thisCache = $this->getMockBuilder(\Neos\Cache\Frontend\FrontendInterface::class)->disableOriginalConstructor()->getMock();
        $thisCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thisCache'));
        $thisBackend = $this->setUpBackend();
        $thisBackend->setCache($thisCache);

        $thatCache = $this->getMockBuilder(\Neos\Cache\Frontend\FrontendInterface::class)->disableOriginalConstructor()->getMock();
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
     * @return PdoBackend
     */
    protected function setUpBackend()
    {
        $mockCache = $this->getMockBuilder(\Neos\Cache\Frontend\FrontendInterface::class)->disableOriginalConstructor()->getMock();
        $mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('TestCache'));

        $mockEnvironmentConfiguration = $this->getMockBuilder(\Neos\Cache\EnvironmentConfiguration::class)->setConstructorArgs([
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ])->getMock();

        $backend = new PdoBackend($mockEnvironmentConfiguration);
        $backend->setCache($mockCache);
        $backend->setDataSourceName('sqlite::memory:');
        $backend->initializeObject();

        return $backend;
    }
}
