<?php
namespace Neos\Cache\Tests\Unit\Psr\SimpleCache;

use Neos\Cache\Backend\BackendInterface;
use Neos\Cache\Exception;
use Neos\Cache\Psr\InvalidArgumentException;
use Neos\Cache\Psr\SimpleCache\SimpleCache;
use Neos\Cache\Tests\BaseTestCase;

/**
 * Tests the PSR-16 simple cache (frontend)
 */
class SimpleCacheTest extends BaseTestCase
{
    /**
     * @var BackendInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockBackend;

    /**
     * @return void
     */
    protected function setUp(): void
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
     */
    public function constructingWithInvalidIdentifierThrowsPsrInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createSimpleCache('Invalid #*<>/()=?!');
    }

    /**
     * @test
     */
    public function setThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $this->expectException(InvalidArgumentException::class);
        $simpleCache = $this->createSimpleCache();
        $simpleCache->set('Invalid #*<>/()=?!', 'does not matter');
    }

    /**
     * @test
     */
    public function setThrowsExceptionOnBackendError()
    {
        $this->expectException(Exception::class);
        $this->mockBackend->expects(self::any())->method('set')->willThrowException(new Exception\InvalidDataException('Some other exception', 1234));
        $simpleCache = $this->createSimpleCache();
        $simpleCache->set('validkey', 'valid data');
    }

    /**
     * @test
     */
    public function setWillSetInBackendAndReturnBackendResponse()
    {
        $this->mockBackend->expects(self::any())->method('set');
        $simpleCache = $this->createSimpleCache();
        $result = $simpleCache->set('validkey', 'valid data');
        self::assertEquals(true, $result);
    }

    /**
     * @test
     */
    public function getThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $this->expectException(InvalidArgumentException::class);
        $simpleCache = $this->createSimpleCache();
        $simpleCache->get('Invalid #*<>/()=?!', false);
    }

    /**
     * @test
     */
    public function getThrowsExceptionOnBackendError()
    {
        $this->expectException(Exception::class);
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
     */
    public function deleteThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $this->expectException(InvalidArgumentException::class);
        $simpleCache = $this->createSimpleCache();
        $simpleCache->delete('Invalid #*<>/()=?!');
    }

    /**
     * @test
     */
    public function deleteThrowsExceptionOnBackendError()
    {
        $this->expectException(Exception::class);
        $this->mockBackend->expects(self::any())->method('remove')->willThrowException(new Exception\InvalidDataException('Some other exception', 1234));
        $simpleCache = $this->createSimpleCache();
        $simpleCache->delete('validkey');
    }

    /**
     * @test
     */
    public function getMultipleThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $this->expectException(InvalidArgumentException::class);
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
     */
    public function setMultipleThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $this->expectException(InvalidArgumentException::class);
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
     */
    public function deleteMultipleThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $this->expectException(InvalidArgumentException::class);
        $simpleCache = $this->createSimpleCache();
        $simpleCache->deleteMultiple(['validKey', 'Invalid #*<>/()=?!']);
    }

    /**
     * @test
     */
    public function hasThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $this->expectException(InvalidArgumentException::class);
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
