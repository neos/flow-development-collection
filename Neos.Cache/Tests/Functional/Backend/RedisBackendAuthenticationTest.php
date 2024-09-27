<?php

namespace Neos\Cache\Tests\Functional\Backend;

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

use Exception;
use Neos\Cache\Backend\RedisBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Tests\BaseTestCase;
use RedisException;

/**
 * Testcase for the redis cache backend
 *
 * These tests use an actual Redis instance and will place and remove keys in db 0!
 * Since all keys have the 'TestCache:' prefix, running the tests should have
 * no side effects on non-related cache entries.
 *
 * Tests require Redis listening on 127.0.0.1:6379. Furthermore, the following users are required
 * default:<no_password>
 * test_no_password:<no_password>
 * test_password:<secret_password>
 *
 * The users can be added by:
 * acl setuser test_no_password on > ~* &* +@all
 * acl setuser test_password on >secret_password ~* &* +@all
 *
 * @requires extension redis
 */
class RedisBackendAuthenticationTest extends BaseTestCase
{
    /**
     * Set up test case
     *
     * @return void
     */
    protected function setUp(): void
    {
        $phpredisVersion = phpversion('redis');
        if (version_compare($phpredisVersion, '5.0.0', '<')) {
            $this->markTestSkipped(sprintf('phpredis extension version %s is not supported. Please update to version 5.0.0+.', $phpredisVersion));
        }
        try {
            if (!@fsockopen('127.0.0.1', 6379)) {
                $this->markTestSkipped('redis server not reachable');
            }
        } catch (Exception $e) {
            $this->markTestSkipped('redis server not reachable');
        }
    }


    /**
     * @test
     */
    public function defaultUserNoPassword()
    {
        $backend = new RedisBackend(
            new EnvironmentConfiguration('Redis a wonderful color Testing', '/some/path', PHP_MAXPATHLEN),
            ['hostname' => '127.0.0.1', 'database' => 0]
        );
        $this->assertInstanceOf('Neos\Cache\Backend\RedisBackend', $backend);
    }

    /**
     * @test
     */
    public function usernameNoPassword()
    {
        $backend = new RedisBackend(
            new EnvironmentConfiguration('Redis a wonderful color Testing', '/some/path', PHP_MAXPATHLEN),
            ['hostname' => '127.0.0.1', 'database' => 0, 'username' => 'test_no_password']
        );
        $this->assertInstanceOf('Neos\Cache\Backend\RedisBackend', $backend);
    }

    /**
     * @test
     */
    public function usernamePassword()
    {
        $backend = new RedisBackend(
            new EnvironmentConfiguration('Redis a wonderful color Testing', '/some/path', PHP_MAXPATHLEN),
            ['hostname' => '127.0.0.1', 'database' => 0, 'username' => 'test_password', 'password' => 'secret_password']
        );
        $this->assertInstanceOf('Neos\Cache\Backend\RedisBackend', $backend);
    }

    /**
     * @test
     */
    public function incorrectUsernamePassword()
    {
        $this->expectException(RedisException::class);
        $backend = new RedisBackend(
            new EnvironmentConfiguration('Redis a wonderful color Testing', '/some/path', PHP_MAXPATHLEN),
            ['hostname' => '127.0.0.1', 'database' => 0, 'username' => 'test_password', 'password' => 'incorrect_password']
        );
    }
}
