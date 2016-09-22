<?php
namespace TYPO3\Flow\Tests\Unit\Cache\Backend;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Core\ApplicationContext;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Cache;

/**
 * Testcase for the abstract cache backend
 */
class AbstractBackendTest extends UnitTestCase
{
    /**
     * @var Cache\Backend\AbstractBackend
     */
    protected $backend;

    /**
     * @return void
     */
    public function setUp()
    {
        $className = 'ConcreteBackend_' . md5(uniqid(mt_rand(), true));
        eval('
			class ' . $className. ' extends \TYPO3\Flow\Cache\Backend\AbstractBackend {
				public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {}
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
        $this->backend = new $className(new ApplicationContext('Testing'));
    }

    /**
     * @test
     */
    public function theConstructorCallsSetterMethodsForAllSpecifiedOptions()
    {
        $className = get_class($this->backend);
        $backend = new $className(new ApplicationContext('Testing'), ['someOption' => 'someValue']);
        $this->assertSame('someValue', $backend->getSomeOption());
    }
}
