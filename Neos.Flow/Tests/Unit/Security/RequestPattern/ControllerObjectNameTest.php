<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Security\RequestPattern;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\RequestPattern\ControllerObjectName;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the controller object name request pattern
 */
final class ControllerObjectNameTest extends UnitTestCase
{
    #[Test]
    public function matchRequestReturnsTrueIfTheCurrentRequestMatchesTheControllerObjectNamePattern()
    {
        $request = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->onlyMethods(['getControllerObjectName'])->getMock();
        $request->expects($this->once())->method('getControllerObjectName')->willReturn(('Neos\Flow\Security\Controller\LoginController'));

        $requestPattern = new ControllerObjectName(['controllerObjectNamePattern' => 'Neos\Flow\Security\.*']);

        self::assertTrue($requestPattern->matchRequest($request));
    }

    #[Test]
    public function matchRequestReturnsFalseIfTheCurrentRequestDoesNotMatchTheControllerObjectNamePattern()
    {
        $request = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->onlyMethods(['getControllerObjectName'])->getMock();
        $request->expects($this->once())->method('getControllerObjectName')->willReturn(('Some\Package\Controller\SomeController'));

        $requestPattern = new ControllerObjectName(['controllerObjectNamePattern' => 'Neos\Flow\Security\.*']);

        self::assertFalse($requestPattern->matchRequest($request));
    }
}
