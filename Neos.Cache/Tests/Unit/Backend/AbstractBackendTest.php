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
    protected function setUp(): void
    {
        class_exists(AbstractBackend::class);
        $className = 'ConcreteBackend_' . md5(uniqid(mt_rand(), true));
        eval('
            class ' . $className . ' extends \Neos\Cache\Backend\AbstractBackend {
                public function set(string $entryIdentifier, string $data, array $tags = [], int $lifetime = NULL): void {}
                public function get(string $entryIdentifier): string {}
                public function has(string $entryIdentifier): bool {}
                public function remove(string $entryIdentifier): bool {}
                public function flush(): void {}
                public function flushByTag(string $tag): int {}
                public function flushByTags(array $tags): int {}
                public function findIdentifiersByTag(string $tag): array {}
                public function collectGarbage(): void {}
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
        self::assertSame('someValue', $backend->getSomeOption());
    }
}
