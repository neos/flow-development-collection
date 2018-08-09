<?php
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
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test case for the Json File Backend
 */
class JsonFileBackendTest extends UnitTestCase
{
    /**
     */
    public function setUp()
    {
        vfsStream::setup('testDirectory');
    }

    /**
     * @test
     */
    public function appendRendersALogEntryAndAppendsItToTheLogfile()
    {
        $logFileUrl = vfsStream::url('testDirectory') . '/test.log';
        $backend = new JsonFileBackend(['logFileUrl' => $logFileUrl]);
        $backend->open();

        $backend->append('the log message', LOG_WARNING, ['foo' => 'bar'], 'Neos.Flow.Log', get_class($this), __FUNCTION__);

        $logLine = file_get_contents('vfs://testDirectory/test.log');
        $actualData = json_decode($logLine, true);
        $this->assertNotFalse($actualData);

        $expectedOrigin = [
            'packageKey' => 'Neos.Flow.Log',
            'className' => get_class($this),
            'methodName' => __FUNCTION__
        ];

        $this->assertGreaterThanOrEqual((new \DateTime($actualData['timestamp']))->getTimestamp(), time());
        $this->assertEquals($actualData['severity'], 'warning');
        $this->assertEquals($actualData['origin'], $expectedOrigin);
        $this->assertEquals($actualData['message'], 'the log message');
        $this->assertEquals($actualData['additionalData'], ['foo' => 'bar']);
        $this->assertEquals($actualData['remoteIp'], '');


        if (function_exists('posix_getpid')) {
            $this->assertEquals($actualData['processId'], posix_getpid());
        }
    }
}
