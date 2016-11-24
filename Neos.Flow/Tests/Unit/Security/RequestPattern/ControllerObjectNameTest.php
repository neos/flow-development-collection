<?php
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

/**
 * Testcase for the controller object name request pattern
 */
class ControllerObjectNameTest extends UnitTestCase
{

    /**
     * @test
     */
    public function matchRequestReturnsTrueIfTheCurrentRequestMatchesTheControllerObjectNamePattern()
    {
        $request = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName'])->getMock();
        $request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('Neos\Flow\Security\Controller\LoginController'));

        $requestPattern = new ControllerObjectName(['controllerObjectNamePattern' => 'Neos\Flow\Security\.*']);

        $this->assertTrue($requestPattern->matchRequest($request));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfTheCurrentRequestDoesNotMatchTheControllerObjectNamePattern()
    {
        $request = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName'])->getMock();
        $request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('Some\Package\Controller\SomeController'));

        $requestPattern = new ControllerObjectName(['controllerObjectNamePattern' => 'Neos\Flow\Security\.*']);

        $this->assertFalse($requestPattern->matchRequest($request));
    }
}
