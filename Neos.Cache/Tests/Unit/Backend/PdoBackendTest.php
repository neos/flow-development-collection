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
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Cache\Tests\BaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;

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
        $thisCache = $this->getMockBuilder(FrontendInterface::class)->disableOriginalConstructor()->getMock();
        $thisCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('thisCache'));
        $thisBackend = $this->setUpBackend();
        $thisBackend->setCache($thisCache);

        $thatCache = $this->getMockBuilder(FrontendInterface::class)->disableOriginalConstructor()->getMock();
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
     * @test
     */
    public function expectBinaryDataForCacheEntries()
    {
        $neosLogo = \file_get_contents('data:image/png;base64,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');

        $backend = $this->setUpBackend();
        $backend->set('neos-logo', $neosLogo);

        self::assertEquals(
            $neosLogo,
            $backend->get('neos-logo')
        );
    }

    /**
     * Sets up the APC backend used for testing
     *
     * @return PdoBackend
     */
    protected function setUpBackend()
    {
        /** @var FrontendInterface|MockObject $mockCache */
        $mockCache = $this->getMockBuilder(FrontendInterface::class)->disableOriginalConstructor()->getMock();
        $mockCache->expects($this->any())->method('getIdentifier')->will($this->returnValue('TestCache'));

        $mockEnvironmentConfiguration = $this->getMockBuilder(EnvironmentConfiguration::class)->setConstructorArgs([
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        ])->getMock();

        $backend = new PdoBackend($mockEnvironmentConfiguration, ['dataSourceName' => 'sqlite::memory:']);
        $backend->setCache($mockCache);

        return $backend;
    }
}
