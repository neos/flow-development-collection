<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers\Security;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
        $mockSecurityContext = $this->createMock('TYPO3\Flow\Security\Context');
        $mockSecurityContext->expects($this->once())->method('getCsrfProtectionToken')->will($this->returnValue('TheCsrfToken'));

        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Security\CsrfTokenViewHelper', array('dummy'));
        $viewHelper->_set('securityContext', $mockSecurityContext);

        $actualResult = $viewHelper->render();
        $this->assertEquals('TheCsrfToken', $actualResult);
    }
}
