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

/**
 * Testcase for the controller object name request pattern
 *
 */
class ControllerObjectNameTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function requestMatchingBasicallyWorks()
    {
        $request = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->setMethods(array('getControllerObjectName'))->getMock();
        $request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('TYPO3\Flow\Security\Controller\LoginController'));

        $requestPattern = new \TYPO3\Flow\Security\RequestPattern\ControllerObjectName();
        $requestPattern->setPattern('TYPO3\Flow\Security\.*');

        $this->assertTrue($requestPattern->matchRequest($request));
        $this->assertEquals('TYPO3\Flow\Security\.*', $requestPattern->getPattern());
    }
}
