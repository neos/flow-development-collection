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

use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Reflection\ReflectionService;
use Neos\FluidAdaptor\Core\Rendering\RenderingContext;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Policy\Role;
use Neos\FluidAdaptor\ViewHelpers\Security\IfHasRoleViewHelper;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test case for IfHasRoleViewHelper
 *
 */
class IfHasRoleViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var IfHasRoleViewHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockViewHelper;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockSecurityContext;

    /**
     * @var PolicyService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPolicyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockViewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Security\IfHasRoleViewHelper::class)->setMethods([
            'renderThenChild',
            'renderElseChild'
        ])->getMock();

        $this->mockSecurityContext = $this->getMockBuilder(\Neos\Flow\Security\Context::class)->disableOriginalConstructor()->getMock();
        $this->mockSecurityContext->expects(self::any())->method('canBeInitialized')->willReturn(true);

        $this->mockPolicyService = $this->getMockBuilder(\Neos\Flow\Security\Policy\PolicyService::class)->disableOriginalConstructor()->getMock();

        $reflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $reflectionService->expects(self::any())->method('getMethodParameters')->willReturn([]);

        $objectManager = $this->getMockBuilder(ObjectManagerInterface::class)->disableOriginalConstructor()->getMock();
        $objectManager->expects(self::any())->method('get')->willReturnCallback(function ($objectName) use ($reflectionService) {
            switch ($objectName) {
                case Context::class:
                    return $this->mockSecurityContext;
                    break;
                case PolicyService::class:
                    return $this->mockPolicyService;
                    break;
                case ReflectionService::class:
                    return $reflectionService;
                    break;
            }
        });

        $renderingContext = $this->getMockBuilder(RenderingContext::class)->disableOriginalConstructor()->getMock();
        $renderingContext->expects(self::any())->method('getObjectManager')->willReturn($objectManager);
        $renderingContext->expects(self::any())->method('getControllerContext')->willReturn($this->getMockControllerContext());

        $this->inject($this->mockViewHelper, 'objectManager', $objectManager);
        $this->inject($this->mockViewHelper, 'renderingContext', $renderingContext);
    }

    /**
     * Create a mock controllerContext
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockControllerContext()
    {
        $httpRequest = new ServerRequest('GET', 'http://robertlemke.com/blog');
        $mockRequest = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockRequest->expects(self::any())->method('getControllerPackageKey')->will(self::returnValue('Acme.Demo'));

        $mockControllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->setMethods(['getRequest'])->disableOriginalConstructor()->getMock();
        $mockControllerContext->expects(self::any())->method('getRequest')->will(self::returnValue($mockRequest));

        return $mockControllerContext;
    }

    /**
     * @test
     */
    public function viewHelperRendersThenPartIfHasRoleReturnsTrue()
    {
        $role = new Role('Acme.Demo:SomeRole');

        $this->mockSecurityContext->expects(self::once())->method('hasRole')->with('Acme.Demo:SomeRole')->will(self::returnValue(true));
        $this->mockPolicyService->expects(self::once())->method('getRole')->with('Acme.Demo:SomeRole')->will(self::returnValue($role));

        $this->mockViewHelper->expects(self::once())->method('renderThenChild')->will(self::returnValue('then-child'));

        $arguments = [
            'role' => 'SomeRole',
            'account' => null
        ];
        $this->mockViewHelper = $this->prepareArguments($this->mockViewHelper, $arguments);
        $actualResult = $this->mockViewHelper->render();
        self::assertEquals('then-child', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperHandlesPackageKeyAttributeCorrectly()
    {
        $this->mockSecurityContext->expects(self::any())->method('hasRole')->will(self::returnCallBack(function ($role) {
            switch ($role) {
                case 'Neos.FluidAdaptor:Administrator':
                    return true;
                case 'Neos.FluidAdaptor:User':
                    return false;
            }
        }));

        $this->mockViewHelper->expects(self::any())->method('renderThenChild')->will(self::returnValue('true'));
        $this->mockViewHelper->expects(self::any())->method('renderElseChild')->will(self::returnValue('false'));

        $arguments = [
            'role' => new Role('Neos.FluidAdaptor:Administrator'),
            'account' => null
        ];
        $this->mockViewHelper = $this->prepareArguments($this->mockViewHelper, $arguments);
        $actualResult = $this->mockViewHelper->render();
        self::assertEquals('true', $actualResult, 'Full role identifier in role argument is accepted');

        $arguments = [
            'role' => new Role('Neos.FluidAdaptor:User'),
            'packageKey' => 'Neos.FluidAdaptor',
            'account' => null
        ];
        $this->mockViewHelper = $this->prepareArguments($this->mockViewHelper, $arguments);
        $actualResult = $this->mockViewHelper->render();
        self::assertEquals('false', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesSpecifiedAccountForCheck()
    {
        $mockAccount = $this->createMock(\Neos\Flow\Security\Account::class);
        $mockAccount->expects(self::any())->method('hasRole')->will(self::returnCallBack(function (Role $role) {
            switch ($role->getIdentifier()) {
                case 'Neos.FluidAdaptor:Administrator':
                    return true;
            }
        }));

        $this->mockViewHelper->expects(self::any())->method('renderThenChild')->will(self::returnValue('true'));
        $this->mockViewHelper->expects(self::any())->method('renderElseChild')->will(self::returnValue('false'));

        $arguments = [
            'role' => new Role('Neos.FluidAdaptor:Administrator'),
            'packageKey' => null,
            'account' => $mockAccount
        ];
        $this->mockViewHelper = $this->prepareArguments($this->mockViewHelper, $arguments);
        $actualResult = $this->mockViewHelper->render();
        self::assertEquals('true', $actualResult, 'Full role identifier in role argument is accepted');
    }
}
