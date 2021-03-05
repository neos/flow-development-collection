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

use Neos\Flow\Log\Backend\AbstractBackend;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the abstract log backend
 */
class AbstractBackendTest extends UnitTestCase
{
    /**
     * @var AbstractBackend
     */
    protected $backendClassName;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->backendClassName = 'ConcreteBackend_' . md5(uniqid(mt_rand(), true));
        eval('
			class ' . $this->backendClassName . ' extends \Neos\Flow\Log\Backend\AbstractBackend {
				public function open(): void {}
				public function append(string $message, int $severity = 1, $additionalData = NULL, string $packageKey = NULL, string $className = NULL, string $methodName = NULL): void {}
				public function close(): void {}
				public function setSomeOption($value) {
					$this->someOption = $value;
				}
				public function getSomeOption() {
					return $this->someOption;
				}
			}
		');
    }

    /**
     * @test
     */
    public function theConstructorCallsSetterMethodsForAllSpecifiedOptions()
    {
        $className = $this->backendClassName;
        $backend = new $className(['someOption' => 'someValue']);
        self::assertSame('someValue', $backend->getSomeOption());
    }
}
