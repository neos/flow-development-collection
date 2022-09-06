<?php
declare(strict_types=1);

namespace Neos\Cache\Tests\Unit\Backend;

include_once(__DIR__ . '/../../BaseTestCase.php');

use Neos\Cache\Backend\MultiBackend;
use Neos\Cache\Backend\NullBackend;
use Neos\Cache\Backend\RedisBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Tests\BaseTestCase;

class MultiBackendTest extends BaseTestCase
{
    /**
     * @test
     */
    public function noExceptionIsThrownIfBackendFailsToBeCreated(): void
    {
        $backendOptions = [
            'backendConfigurations' => [
                [
                    // Will fail as there shouldn't be a redis usually on that port
                    'backend' => RedisBackend::class,
                    'backendOptions' => [
                        'port' => '60999'
                    ]
                ]
            ]
        ];

        $multiBackend = new MultiBackend($this->getEnvironmentConfiguration(), $backendOptions);
        // We need to trigger initialization.
        $result = $multiBackend->get('foo');
        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function debugModeWillBubbleExceptions(): void
    {
        $this->expectException(\Throwable::class);
        $backendOptions = [
            'debug' => true,
            'backendConfigurations' => [
                [
                    // Will fail as there shouldn't be a redis usually on that port
                    'backend' => RedisBackend::class,
                    'backendOptions' => [
                        'port' => '60999'
                    ]
                ]
            ]
        ];

        $multiBackend = new MultiBackend($this->getEnvironmentConfiguration(), $backendOptions);
        // We need to trigger initialization.
        $result = $multiBackend->get('foo');
        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function writesToAllBackends(): void
    {
        $mockBuilder = $this->getMockBuilder(NullBackend::class);
        $firstNullBackendMock = $mockBuilder->getMock();
        $secondNullBackendMock = $mockBuilder->getMock();

        $firstNullBackendMock->expects(self::once())->method('set')->withAnyParameters();
        $secondNullBackendMock->expects(self::once())->method('set')->withAnyParameters();

        $multiBackend = new MultiBackend($this->getEnvironmentConfiguration(), []);
        $this->inject($multiBackend, 'backends', [$firstNullBackendMock, $secondNullBackendMock]);
        $this->inject($multiBackend, 'initialized', true);

        $multiBackend->set('foo', 'data');
    }

    /**
     * @test
     */
    public function fallsBackToSecondaryBackend(): void
    {
        $mockBuilder = $this->getMockBuilder(NullBackend::class);
        $firstNullBackendMock = $mockBuilder->getMock();
        $secondNullBackendMock = $mockBuilder->getMock();

        $firstNullBackendMock->expects(self::once())->method('get')->with('foo')->willThrowException(new \Exception('Backend failure'));
        $secondNullBackendMock->expects(self::once())->method('get')->with('foo')->willReturn(5);

        $multiBackend = new MultiBackend($this->getEnvironmentConfiguration(), []);
        $this->inject($multiBackend, 'backends', [$firstNullBackendMock, $secondNullBackendMock]);
        $this->inject($multiBackend, 'initialized', true);

        $result = $multiBackend->get('foo');
        self::assertSame(5, $result);
    }

    /**
     * @test
     */
    public function removesUnhealthyBackend(): void
    {
        $mockBuilder = $this->getMockBuilder(NullBackend::class);
        $firstNullBackendMock = $mockBuilder->getMock();
        $secondNullBackendMock = $mockBuilder->getMock();

        $firstNullBackendMock->expects(self::once())->method('get')->with('foo')->willThrowException(new \Exception('Backend failure'));
        $secondNullBackendMock->expects(self::exactly(2))->method('get')->with('foo')->willReturn(5);

        $multiBackend = new MultiBackend($this->getEnvironmentConfiguration(), []);
        $multiBackend->setRemoveUnhealthyBackends(true);

        $this->inject($multiBackend, 'backends', [$firstNullBackendMock, $secondNullBackendMock]);
        $this->inject($multiBackend, 'initialized', true);

        $result = $multiBackend->get('foo');
        self::assertSame(5, $result);
        $result = $multiBackend->get('foo');
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
