<?php
namespace Neos\Cache\Tests\Unit\Psr\SimpleCache;

use Neos\Cache\Backend\BackendInterface;
use Neos\Cache\Exception;
use Neos\Cache\Psr\SimpleCache\SimpleCache;
use Neos\Cache\Tests\BaseTestCase;

/**
 * Tests the PSR-16 simple cache (frontend)
 */
class SimpleCacheTest extends BaseTestCase
{
    /**
     * @var BackendInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockBackend;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->mockBackend = $this->getMockBuilder(BackendInterface::class)->getMock();
    }

    /**
     * @param string $identifier
     * @return SimpleCache
     */
    protected function createSimpleCache($identifier = 'SimpleCacheTest')
    {
        return new SimpleCache($identifier, $this->mockBackend);
    }

    /**
     * @test
     * @expectedException \Neos\Cache\Psr\InvalidArgumentException
     */
    public function constructingWithInvalidIdentifierThrowsPsrInvalidArgumentException()
    {
        $this->createSimpleCache('Invalid #*<>/()=?!');
    }

    /**
     * @test
     * @expectedException \Neos\Cache\Psr\InvalidArgumentException
     */
    public function setThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $simpleCache = $this->createSimpleCache();
        $simpleCache->set('Invalid #*<>/()=?!', 'does not matter');
    }

    /**
     * @test
     * @expectedException Exception
     */
    public function setThrowsExceptionOnBackendError()
    {
        $this->mockBackend->expects(self::any())->method('set')->willThrowException(new Exception\InvalidDataException('Some other exception', 1234));
        $simpleCache = $this->createSimpleCache();
        $simpleCache->set('validkey', 'valid data');
    }

    /**
     * @test
     */
    public function setWillSetInBackendAndReturnBackendResponse()
    {
        $this->mockBackend->expects(self::any())->method('set')->willReturn(true);
        $simpleCache = $this->createSimpleCache();
        $result = $simpleCache->set('validkey', 'valid data');
        self::assertEquals(true, $result);
    }

    /**
     * @test
     * @expectedException \Neos\Cache\Psr\InvalidArgumentException
     */
    public function getThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $simpleCache = $this->createSimpleCache();
        $simpleCache->get('Invalid #*<>/()=?!', false);
    }

    /**
     * @test
     * @expectedException Exception
     */
    public function getThrowsExceptionOnBackendError()
    {
        $this->mockBackend->expects(self::any())->method('get')->willThrowException(new Exception\InvalidDataException('Some other exception', 1234));
        $simpleCache = $this->createSimpleCache();
        $simpleCache->get('validkey', false);
    }

    /**
     * @test
     */
    public function getReturnsDefaultValueIfBackendFoundNoEntry()
    {
        $defaultValue = 'fallback';
        $this->mockBackend->expects(self::any())->method('get')->willReturn(false);
        $simpleCache = $this->createSimpleCache();
        $result = $simpleCache->get('validkey', $defaultValue);
        self::assertEquals($defaultValue, $result);
    }

    /**
     * Somewhat brittle test as we know that the cache serializes. Might want to extract that to a separate Serializer?
     * @test
     */
    public function getReturnsBackendResponseAfterUnserialising()
    {
        $cachedValue = [1, 2, 3];
        $this->mockBackend->expects(self::any())->method('get')->willReturn(serialize($cachedValue));
        $simpleCache = $this->createSimpleCache();
        $result = $simpleCache->get('validkey');
        self::assertEquals($cachedValue, $result);
    }

    /**
     * @test
     * @expectedException \Neos\Cache\Psr\InvalidArgumentException
     */
    public function deleteThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $simpleCache = $this->createSimpleCache();
        $simpleCache->delete('Invalid #*<>/()=?!');
    }

    /**
     * @test
     * @expectedException Exception
     */
    public function deleteThrowsExceptionOnBackendError()
    {
        $this->mockBackend->expects(self::any())->method('remove')->willThrowException(new Exception\InvalidDataException('Some other exception', 1234));
        $simpleCache = $this->createSimpleCache();
        $simpleCache->delete('validkey');
    }

    /**
     * @test
     * @expectedException \Neos\Cache\Psr\InvalidArgumentException
     */
    public function getMultipleThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $simpleCache = $this->createSimpleCache();
        $simpleCache->getMultiple(['validKey', 'Invalid #*<>/()=?!']);
    }

    /**
     * @test
     */
    public function getMultipleGetsMultipleValues()
    {
        $this->mockBackend->expects(self::any())->method('get')->willReturnMap([
            ['validKey', serialize('entry1')],
            ['another', serialize('entry2')]
        ]);
        $simpleCache = $this->createSimpleCache();
        $result = $simpleCache->getMultiple(['validKey', 'another']);
        self::assertEquals(['validKey' => 'entry1', 'another' => 'entry2'], $result);
    }

    /**
     * @test
     */
    public function getMultipleFillsWithDefault()
    {
        $this->mockBackend->expects(self::any())->method('get')->willReturnMap([
            ['validKey', serialize('entry1')],
            ['notExistingEntry', false]
        ]);
        $simpleCache = $this->createSimpleCache();
        $result = $simpleCache->getMultiple(['validKey', 'notExistingEntry'], 'FALLBACK');
        self::assertEquals(['validKey' => 'entry1', 'notExistingEntry' => 'FALLBACK'], $result);
    }

    /**
     * @test
     * @expectedException \Neos\Cache\Psr\InvalidArgumentException
     */
    public function setMultipleThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $simpleCache = $this->createSimpleCache();
        $simpleCache->setMultiple(['validKey' => 'value', 'Invalid #*<>/()=?!' => 'value']);
    }

    /**
     * Moot test at the momment, as our backends never return so this is always true.
     *
     * @test
     */
    public function setMultipleReturnsResult()
    {
        $this->mockBackend->expects(self::any())->method('set')->willReturnMap([
            ['validKey', 'value', true],
            ['another', 'value', true]
        ]);

        $simpleCache = $this->createSimpleCache();
        $result = $simpleCache->setMultiple(['validKey' => 'value', 'another' => 'value']);
        self::assertEquals(true, $result);
    }

    /**
     * @test
     * @expectedException \Neos\Cache\Psr\InvalidArgumentException
     */
    public function deleteMultipleThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $simpleCache = $this->createSimpleCache();
        $simpleCache->deleteMultiple(['validKey', 'Invalid #*<>/()=?!']);
    }

    /**
     * @test
     * @expectedException \Neos\Cache\Psr\InvalidArgumentException
     */
    public function hasThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $simpleCache = $this->createSimpleCache();
        $simpleCache->has('Invalid #*<>/()=?!');
    }

    /**
     * @test
     */
    public function hasReturnsWhatTheBackendSays()
    {
        $this->mockBackend->expects(self::any())->method('has')->willReturnMap([
            ['existing', true],
            ['notExisting', false]
        ]);

        $simpleCache = $this->createSimpleCache();
        $result = $simpleCache->has('existing');
        self::assertEquals(true, $result);

        $result = $simpleCache->has('notExisting');
        self::assertEquals(false, $result);
    }
}
