<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Security;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;

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
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockSecurityContext->expects($this->once())->method('getCsrfProtectionToken')->will($this->returnValue('TheCsrfToken'));

        $viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Security\CsrfTokenViewHelper::class, array('dummy'));
        $viewHelper->_set('securityContext', $mockSecurityContext);

        $actualResult = $viewHelper->render();
        $this->assertEquals('TheCsrfToken', $actualResult);
    }
}
