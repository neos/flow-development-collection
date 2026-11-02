<?php

declare(strict_types=1);

namespace Neos\Flow\Log\Tests\Unit\Backend;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Log\Backend\JsonFileBackend;
use Neos\Flow\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test case for the Json File Backend
 */
final class JsonFileBackendTest extends UnitTestCase
{
    /**
     */
    protected function setUp(): void
    {
        vfsStream::setup('testDirectory');
    }

    #[Test]
    public function appendRendersALogEntryAndAppendsItToTheLogfile()
    {
        $logFileUrl = vfsStream::url('testDirectory') . '/test.log';
        $backend = new JsonFileBackend(['logFileUrl' => $logFileUrl]);
        $backend->open();

        $backend->append('the log message', LOG_WARNING, ['foo' => 'bar'], 'Neos.Flow.Log', get_class($this), __FUNCTION__);

        $logLine = file_get_contents('vfs://testDirectory/test.log');
        $actualData = json_decode($logLine, true);
        self::assertNotFalse($actualData);

        $expectedOrigin = [
            'packageKey' => 'Neos.Flow.Log',
            'className' => get_class($this),
            'methodName' => __FUNCTION__
        ];

        self::assertGreaterThanOrEqual((new \DateTime($actualData['timestamp']))->getTimestamp(), time());
        self::assertEquals('warning', $actualData['severity']);
        self::assertEquals($actualData['origin'], $expectedOrigin);
        self::assertEquals('the log message', $actualData['message']);
        self::assertEquals($actualData['additionalData'], ['foo' => 'bar']);
        self::assertEquals('', $actualData['remoteIp']);


        if (function_exists('posix_getpid')) {
            self::assertEquals($actualData['processId'], posix_getpid());
        }
    }
}
