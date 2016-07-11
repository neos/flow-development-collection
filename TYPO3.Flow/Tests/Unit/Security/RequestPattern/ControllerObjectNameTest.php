<?php
namespace TYPO3\Flow\Tests\Unit\Security\RequestPattern;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Security\RequestPattern\ControllerObjectName;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the controller object name request pattern
 *
 */
class ControllerObjectNameTest extends UnitTestCase
{

    /**
     * @test
     */
    public function matchRequestReturnsTrueIfTheCurrentRequestMatchesTheControllerObjectNamePattern()
    {
        $request = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->setMethods(array('getControllerObjectName'))->getMock();
        $request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('TYPO3\Flow\Security\Controller\LoginController'));

        $requestPattern = new ControllerObjectName(['controllerObjectNamePattern' => 'TYPO3\Flow\Security\.*']);

        $this->assertTrue($requestPattern->matchRequest($request));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfTheCurrentRequestDoesNotMatchTheControllerObjectNamePattern()
    {
        $request = $this->getMock(ActionRequest::class, array('getControllerObjectName'), array(), '', false);
        $request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('Some\Package\Controller\SomeController'));

        $requestPattern = new ControllerObjectName(['controllerObjectNamePattern' => 'TYPO3\Flow\Security\.*']);

        $this->assertFalse($requestPattern->matchRequest($request));
    }
}
