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
use Neos\Flow\Log\Backend\AbstractBackend;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the abstract log backend
 */
final class AbstractBackendTest extends UnitTestCase
{
    #[Test]
    public function theConstructorCallsSetterMethodsForAllSpecifiedOptions()
    {
        $backend = new class (['someOption' => 'someValue']) extends AbstractBackend {
            protected $someOption;
            public function open(): void
            {
            }
            public function append(string $message, int $severity = 1, $additionalData = null, ?string $packageKey = null, ?string $className = null, ?string $methodName = null): void
            {
            }
            public function close(): void
            {
            }
            public function setSomeOption($value)
            {
                $this->someOption = $value;
            }
            public function getSomeOption()
            {
                return $this->someOption;
            }
        };
        self::assertSame('someValue', $backend->getSomeOption());
    }
}
