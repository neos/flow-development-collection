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
 */
class ControllerObjectNameTest extends UnitTestCase
{
    /**
     * @test
     */
    public function requestMatchingBasicallyWorks()
    {
        $request = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName'])->getMock();
        $request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('TYPO3\Flow\Security\Controller\LoginController'));

        $requestPattern = new ControllerObjectName();
        $requestPattern->setPattern('TYPO3\Flow\Security\.*');

        $this->assertTrue($requestPattern->matchRequest($request));
        $this->assertEquals('TYPO3\Flow\Security\.*', $requestPattern->getPattern());
    }
}
