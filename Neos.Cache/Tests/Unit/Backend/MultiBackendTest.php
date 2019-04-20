<?php
namespace Neos\Cache\Tests\Unit\Backend;

include_once(__DIR__ . '/../../BaseTestCase.php');

use Neos\Cache\Backend\MultiBackend;
use Neos\Cache\Backend\NullBackend;
use Neos\Cache\Backend\RedisBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Tests\BaseTestCase;

/**
 *
 */
class MultiBackendTest extends BaseTestCase
{
    /**
     * @test
     */
    public function noExceptionIsThrownIfBackendFailsToBeCreated()
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
     * @expectedException \Throwable
     */
    public function debugModeWillBubbleExceptions()
    {
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
    public function writesToAllBackends()
    {
        $mockBuilder = $this->getMockBuilder(NullBackend::class);
        $firstNullBackendMock = $mockBuilder->getMock();
        $secondNullBackendMock = $mockBuilder->getMock();

        $firstNullBackendMock->expects(self::once())->method('set')->withAnyParameters()->willReturn(null);
        $secondNullBackendMock->expects(self::once())->method('set')->withAnyParameters()->willReturn(null);

        $multiBackend = new MultiBackend($this->getEnvironmentConfiguration(), []);
        $this->inject($multiBackend, 'backends', [$firstNullBackendMock, $secondNullBackendMock]);
        $this->inject($multiBackend, 'initialized', true);

        $multiBackend->set('foo', 1);
    }

    /**
     * @test
     */
    public function fallsBackToSecondaryBackend()
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
     * @return EnvironmentConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getEnvironmentConfiguration()
    {
        return new EnvironmentConfiguration(
            __DIR__ . '~Testing',
            'vfs://Foo/',
            255
        );
    }
}
