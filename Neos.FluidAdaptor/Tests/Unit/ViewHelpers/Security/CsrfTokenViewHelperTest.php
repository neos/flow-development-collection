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

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Security\CsrfTokenViewHelper;

/**
 * Test case for the CsrfTokenViewHelper
 */
class CsrfTokenViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var CsrfTokenViewHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $viewHelper;

    /**
     * @var ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $objectManagerMock;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(CsrfTokenViewHelper::class)->setMethods(['buildRenderChildrenClosure'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();
        $this->renderingContext->injectObjectManager($this->objectManagerMock);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function viewHelperRendersTheCsrfTokenReturnedFromTheSecurityContext()
    {
        $mockSecurityContext = $this->createMock(\Neos\Flow\Security\Context::class);
        $mockSecurityContext->expects(self::once())->method('getCsrfProtectionToken')->will(self::returnValue('TheCsrfToken'));
        $this->objectManagerMock->expects(self::any())->method('get')->willReturn($mockSecurityContext);

        $actualResult = $this->viewHelper->render();
        self::assertEquals('TheCsrfToken', $actualResult);
    }
}
