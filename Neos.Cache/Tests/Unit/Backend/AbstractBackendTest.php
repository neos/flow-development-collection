<?php
namespace Neos\Cache\Tests\Unit\Backend;

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

use Neos\Cache\Backend\AbstractBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Tests\BaseTestCase;

/**
 * Testcase for the abstract cache backend
 *
 */
class AbstractBackendTest extends BaseTestCase
{
    /**
     * @var AbstractBackend
     */
    protected $backend;

    /**
     * @return void
     */
    public function setUp()
    {
        class_exists(AbstractBackend::class);
        $className = 'ConcreteBackend_' . md5(uniqid(mt_rand(), true));
        eval('
            class ' . $className . ' extends \Neos\Cache\Backend\AbstractBackend {
                public function set($entryIdentifier, $data, array $tags = [], $lifetime = NULL) {}
                public function get($entryIdentifier) {}
                public function has($entryIdentifier) {}
                public function remove($entryIdentifier) {}
                public function flush() {}
                public function flushByTag($tag) {}
                public function findIdentifiersByTag($tag) {}
                public function collectGarbage() {}
                public function setSomeOption($value) {
                    $this->someOption = $value;
                }
                public function getSomeOption() {
                    return $this->someOption;
                }
            }
        ');
        $this->backend = new $className(new EnvironmentConfiguration('Ultraman Neos Testing', '/some/path', PHP_MAXPATHLEN));
    }

    /**
     * @test
     */
    public function theConstructorCallsSetterMethodsForAllSpecifiedOptions()
    {
        $className = get_class($this->backend);
        $backend = new $className(new EnvironmentConfiguration('Ultraman Neos Testing', '/some/path', PHP_MAXPATHLEN), ['someOption' => 'someValue']);
        $this->assertSame('someValue', $backend->getSomeOption());
    }
}
