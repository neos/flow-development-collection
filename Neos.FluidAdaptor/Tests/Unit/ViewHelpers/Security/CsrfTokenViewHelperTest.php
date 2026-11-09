<?php

declare(strict_types=1);

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
use Neos\Flow\Security\Context;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Security\CsrfTokenViewHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test case for the CsrfTokenViewHelper
 */
final class CsrfTokenViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var CsrfTokenViewHelper|MockObject
     */
    protected $viewHelper;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManagerMock;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(CsrfTokenViewHelper::class)->onlyMethods(['buildRenderChildrenClosure'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $this->renderingContext->injectObjectManager($this->objectManagerMock);
        $this->viewHelper->initializeArguments();
    }

    #[Test]
    public function viewHelperRendersTheCsrfTokenReturnedFromTheSecurityContext()
    {
        $mockSecurityContext = $this->createMock(Context::class);
        $mockSecurityContext->expects($this->once())->method('getCsrfProtectionToken')->willReturn(('TheCsrfToken'));
        $this->objectManagerMock->method('get')->willReturn($mockSecurityContext);

        $actualResult = $this->viewHelper->render();
        self::assertEquals('TheCsrfToken', $actualResult);
    }
}
