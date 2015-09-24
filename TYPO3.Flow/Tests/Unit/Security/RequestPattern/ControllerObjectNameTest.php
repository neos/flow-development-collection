<?php
namespace TYPO3\Flow\Tests\Unit\Security\RequestPattern;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
        $request = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array('getControllerObjectName'), array(), '', false);
        $request->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('TYPO3\Flow\Security\Controller\LoginController'));

        $requestPattern = new \TYPO3\Flow\Security\RequestPattern\ControllerObjectName();
        $requestPattern->setPattern('TYPO3\Flow\Security\.*');

        $this->assertTrue($requestPattern->matchRequest($request));
        $this->assertEquals('TYPO3\Flow\Security\.*', $requestPattern->getPattern());
    }
}
