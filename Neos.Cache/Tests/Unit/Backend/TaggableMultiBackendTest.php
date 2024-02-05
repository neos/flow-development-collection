<?php
declare(strict_types=1);

namespace Neos\Cache\Tests\Unit\Backend;

include_once(__DIR__ . '/../../BaseTestCase.php');

use Neos\Cache\Backend\NullBackend;
use Neos\Cache\Backend\TaggableMultiBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Tests\BaseTestCase;

class TaggableMultiBackendTest extends BaseTestCase
{
    /**
     * @test
     */
    public function flushByTagReturnsCountOfFlushedEntries(): void
    {
        $mockBuilder = $this->getMockBuilder(NullBackend::class);
        $firstNullBackendMock = $mockBuilder->getMock();
        $secondNullBackendMock = $mockBuilder->getMock();
        $thirdNullBackendMock = $mockBuilder->getMock();

        $firstNullBackendMock->expects(self::once())->method('flushByTag')->with('foo')->willReturn(2);
        $secondNullBackendMock->expects(self::once())->method('flushByTag')->with('foo')->willThrowException(new \RuntimeException());
        $thirdNullBackendMock->expects(self::once())->method('flushByTag')->with('foo')->willReturn(3);

        $multiBackend = new TaggableMultiBackend($this->getEnvironmentConfiguration(), []);
        $this->inject($multiBackend, 'backends', [$firstNullBackendMock, $secondNullBackendMock, $thirdNullBackendMock]);
        $this->inject($multiBackend, 'initialized', true);

        $result = $multiBackend->flushByTag('foo');
        self::assertSame(5, $result);
    }

    /**
     * @return EnvironmentConfiguration
     */
    public function getEnvironmentConfiguration(): EnvironmentConfiguration
    {
        return new EnvironmentConfiguration(
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        );
    }
}
