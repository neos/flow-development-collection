<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers\Security;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the CsrfTokenViewHelper
 */
class CsrfTokenViewHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function viewHelperRendersTheCsrfTokenReturnedFromTheSecurityContext()
    {
        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
        $mockSecurityContext->expects($this->once())->method('getCsrfProtectionToken')->will($this->returnValue('TheCsrfToken'));

        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Security\CsrfTokenViewHelper', array('dummy'));
        $viewHelper->_set('securityContext', $mockSecurityContext);

        $actualResult = $viewHelper->render();
        $this->assertEquals('TheCsrfToken', $actualResult);
    }
}
