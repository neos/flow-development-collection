<?php

declare(strict_types=1);

namespace Neos\Cache\Tests\Unit\Psr\SimpleCache;

use Neos\Cache\Backend\BackendInterface;
use Neos\Cache\Exception;
use Neos\Cache\Exception\InvalidDataException;
use Neos\Cache\Psr\InvalidArgumentException;
use Neos\Cache\Psr\SimpleCache\SimpleCache;
use Neos\Cache\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests the PSR-16 simple cache (frontend)
 */
final class SimpleCacheTest extends BaseTestCase
{
    /**
     * @var BackendInterface|MockObject
     */
    protected $mockBackend;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->mockBackend = $this->createMock(BackendInterface::class);
    }

    /**
     * @param string $identifier
     * @return SimpleCache
     */
    protected function createSimpleCache($identifier = 'SimpleCacheTest')
    {
        return new SimpleCache($identifier, $this->mockBackend);
    }

    #[Test]
    public function constructingWithInvalidIdentifierThrowsPsrInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createSimpleCache('Invalid #*<>/()=?!');
    }

    #[Test]
    public function setThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $this->expectException(InvalidArgumentException::class);
        $simpleCache = $this->createSimpleCache();
        $simpleCache->set('Invalid #*<>/()=?!', 'does not matter');
    }

    #[Test]
    public function setThrowsExceptionOnBackendError()
    {
        $this->expectException(Exception::class);
        $this->mockBackend->method('set')->willThrowException(new InvalidDataException('Some other exception', 1234));
        $simpleCache = $this->createSimpleCache();
        $simpleCache->set('validkey', 'valid data');
    }

    #[Test]
    public function setWillSetInBackendAndReturnBackendResponse()
    {
        $this->mockBackend->method('set');
        $simpleCache = $this->createSimpleCache();
        $result = $simpleCache->set('validkey', 'valid data');
        self::assertEquals(true, $result);
    }

    #[Test]
    public function getThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $this->expectException(InvalidArgumentException::class);
        $simpleCache = $this->createSimpleCache();
        $simpleCache->get('Invalid #*<>/()=?!', false);
    }

    #[Test]
    public function getThrowsExceptionOnBackendError()
    {
        $this->expectException(Exception::class);
        $this->mockBackend->method('get')->willThrowException(new InvalidDataException('Some other exception', 1234));
        $simpleCache = $this->createSimpleCache();
        $simpleCache->get('validkey', false);
    }

    #[Test]
    public function getReturnsDefaultValueIfBackendFoundNoEntry()
    {
        $defaultValue = 'fallback';
        $this->mockBackend->method('get')->willReturn(false);
        $simpleCache = $this->createSimpleCache();
        $result = $simpleCache->get('validkey', $defaultValue);
        self::assertEquals($defaultValue, $result);
    }

    /**
     * Somewhat brittle test as we know that the cache serializes. Might want to extract that to a separate Serializer?
     */
    #[Test]
    public function getReturnsBackendResponseAfterUnserialising()
    {
        $cachedValue = [1, 2, 3];
        $this->mockBackend->method('get')->willReturn(serialize($cachedValue));
        $simpleCache = $this->createSimpleCache();
        $result = $simpleCache->get('validkey');
        self::assertEquals($cachedValue, $result);
    }

    #[Test]
    public function deleteThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $this->expectException(InvalidArgumentException::class);
        $simpleCache = $this->createSimpleCache();
        $simpleCache->delete('Invalid #*<>/()=?!');
    }

    #[Test]
    public function deleteThrowsExceptionOnBackendError()
    {
        $this->expectException(Exception::class);
        $this->mockBackend->method('remove')->willThrowException(new InvalidDataException('Some other exception', 1234));
        $simpleCache = $this->createSimpleCache();
        $simpleCache->delete('validkey');
    }

    #[Test]
    public function getMultipleThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $this->expectException(InvalidArgumentException::class);
        $simpleCache = $this->createSimpleCache();
        $simpleCache->getMultiple(['validKey', 'Invalid #*<>/()=?!']);
    }

    #[Test]
    public function getMultipleGetsMultipleValues()
    {
        $this->mockBackend->method('get')->willReturnMap([
            ['validKey', serialize('entry1')],
            ['another', serialize('entry2')]
        ]);
        $simpleCache = $this->createSimpleCache();
        $result = $simpleCache->getMultiple(['validKey', 'another']);
        self::assertEquals(['validKey' => 'entry1', 'another' => 'entry2'], $result);
    }

    #[Test]
    public function getMultipleFillsWithDefault()
    {
        $this->mockBackend->method('get')->willReturnMap([
            ['validKey', serialize('entry1')],
            ['notExistingEntry', false]
        ]);
        $simpleCache = $this->createSimpleCache();
        $result = $simpleCache->getMultiple(['validKey', 'notExistingEntry'], 'FALLBACK');
        self::assertEquals(['validKey' => 'entry1', 'notExistingEntry' => 'FALLBACK'], $result);
    }

    #[Test]
    public function setMultipleThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $this->expectException(InvalidArgumentException::class);
        $simpleCache = $this->createSimpleCache();
        $simpleCache->setMultiple(['validKey' => 'value', 'Invalid #*<>/()=?!' => 'value']);
    }

    /**
     * Moot test at the momment, as our backends never return so this is always true.
     */
    #[Test]
    public function setMultipleReturnsResult()
    {
        $this->mockBackend->method('set')->willReturnMap([
            ['validKey', 'value', true],
            ['another', 'value', true]
        ]);

        $simpleCache = $this->createSimpleCache();
        $result = $simpleCache->setMultiple(['validKey' => 'value', 'another' => 'value']);
        self::assertEquals(true, $result);
    }

    #[Test]
    public function deleteMultipleThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $this->expectException(InvalidArgumentException::class);
        $simpleCache = $this->createSimpleCache();
        $simpleCache->deleteMultiple(['validKey', 'Invalid #*<>/()=?!']);
    }

    #[Test]
    public function hasThrowsInvalidArgumentExceptionOnInvalidIdentifier()
    {
        $this->expectException(InvalidArgumentException::class);
        $simpleCache = $this->createSimpleCache();
        $simpleCache->has('Invalid #*<>/()=?!');
    }

    #[Test]
    public function hasReturnsWhatTheBackendSays()
    {
        $this->mockBackend->method('has')->willReturnMap([
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
