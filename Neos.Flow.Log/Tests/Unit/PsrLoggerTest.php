<?php
namespace Neos\Flow\Log\Tests\Unit;

use Neos\Flow\Log\Backend\BackendInterface;
use Neos\Flow\Log\PsrLogger;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Log\LogLevel;

/**
 * Test case for PSR-3 based logger.
 */
class PsrLoggerTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function logLevelDataSource()
    {
        return [
            [LogLevel::EMERGENCY, LOG_EMERG, false],
            [LogLevel::DEBUG, LOG_DEBUG, false],
            [LogLevel::INFO, LOG_INFO, false],
            [LogLevel::NOTICE, LOG_NOTICE, false],
            [LogLevel::WARNING, LOG_WARNING, false],
            [LogLevel::ERROR, LOG_ERR, false],
            [LogLevel::CRITICAL, LOG_CRIT, false],
            [LogLevel::ALERT, LOG_ALERT, false],
            ['non existing loglevel', 'does not matter', true]
        ];
    }

    /**
     * @dataProvider logLevelDataSource
     * @test
     *
     * @param string $psrLogLevel
     * @param int $legacyLogLevel
     * @param bool $willError
     */
    public function logAcceptsOnlyValidLogLevels($psrLogLevel, $legacyLogLevel, $willError)
    {
        $mockBackend = $this->createMock(BackendInterface::class);
        if (!$willError) {
            $mockBackend->expects(self::once())->method('append')->with('some message', $legacyLogLevel)->willReturn(null);
        }
        $psrLogger = new PsrLogger([$mockBackend]);

        try {
            $psrLogger->log($psrLogLevel, 'some message');
        } catch (\Throwable $throwable) {
            $this->assertTrue($willError, $throwable->getMessage());
        }
    }

    /**
     * @dataProvider logLevelDataSource
     * @test
     *
     * @param string $psrLogLevel
     * @param int $legacyLogLevel
     * @param bool $willError
     */
    public function levelSpecificMethodsAreSupported($psrLogLevel, $legacyLogLevel, $willError)
    {
        $mockBackend = $this->createMock(BackendInterface::class);
        $mockBackend->expects(self::once())->method('append')->with('some message', $legacyLogLevel)->willReturn(null);

        $psrLogger = new PsrLogger([$mockBackend]);

        if ($willError) {
            $this->markTestSkipped('unnecessary');
        }

        $psrLogger->$psrLogLevel('some message');
    }

    /**
     * @test
     */
    public function logSupportsContext()
    {
        $message = 'some message';
        $context = ['something' => 123, 'else' => true];
        $mockBackend = $this->createMock(BackendInterface::class);
        $mockBackend->expects(self::once())->method('append')->with('some message', LOG_INFO, $context)->willReturn(null);

        $psrLogger = new PsrLogger([$mockBackend]);
        $psrLogger->log(LogLevel::INFO, $message, $context);
    }
}
