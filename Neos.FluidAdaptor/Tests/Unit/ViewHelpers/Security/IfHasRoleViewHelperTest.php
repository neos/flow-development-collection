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
use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Policy\Role;
use Neos\FluidAdaptor\Core\Rendering\RenderingContext;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Security\IfHasRoleViewHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test case for IfHasRoleViewHelper
 *
 */
final class IfHasRoleViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var IfHasRoleViewHelper|MockObject
     */
    protected $mockViewHelper;

    /**
     * @var Context|MockObject
     */
    protected $mockSecurityContext;

    /**
     * @var PolicyService|MockObject
     */
    protected $mockPolicyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockViewHelper = $this->getMockBuilder(IfHasRoleViewHelper::class)->onlyMethods([
            'renderThenChild',
            'renderElseChild'
        ])->getMock();

        $this->mockSecurityContext = $this->createMock(Context::class);
        $this->mockSecurityContext->method('canBeInitialized')->willReturn(true);

        $this->mockPolicyService = $this->createMock(PolicyService::class);

        $reflectionService = $this->createMock(ReflectionService::class);
        $reflectionService->method('getMethodParameters')->willReturn([]);

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback(function ($objectName) use ($reflectionService) {
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

        $renderingContext = $this->createMock(RenderingContext::class);
        $renderingContext->method('getObjectManager')->willReturn($objectManager);
        $renderingContext->method('getControllerContext')->willReturn($this->getMockControllerContext());

        $this->inject($this->mockViewHelper, 'objectManager', $objectManager);
        $this->inject($this->mockViewHelper, 'renderingContext', $renderingContext);
    }

    /**
     * Create a mock controllerContext
     *
     * @return MockObject
     */
    protected function getMockControllerContext()
    {
        $httpRequest = new ServerRequest('GET', 'http://robertlemke.com/blog');
        $mockRequest = $this->createMock(ActionRequest::class);
        $mockRequest->method('getControllerPackageKey')->willReturn(('Acme.Demo'));

        $mockControllerContext = $this->getMockBuilder(ControllerContext::class)->onlyMethods(['getRequest'])->disableOriginalConstructor()->getMock();
        $mockControllerContext->method('getRequest')->willReturn(($mockRequest));

        return $mockControllerContext;
    }

    #[Test]
    public function viewHelperRendersThenPartIfHasRoleReturnsTrue()
    {
        $role = new Role('Acme.Demo:SomeRole');

        $this->mockSecurityContext->expects($this->once())->method('hasRole')->with('Acme.Demo:SomeRole')->willReturn((true));
        $this->mockPolicyService->expects($this->once())->method('getRole')->with('Acme.Demo:SomeRole')->willReturn(($role));

        $this->mockViewHelper->expects($this->once())->method('renderThenChild')->willReturn(('then-child'));

        $arguments = [
            'role' => 'SomeRole',
            'account' => null
        ];
        $this->mockViewHelper = $this->prepareArguments($this->mockViewHelper, $arguments);
        $actualResult = $this->mockViewHelper->render();
        self::assertEquals('then-child', $actualResult);
    }

    #[Test]
    public function viewHelperHandlesPackageKeyAttributeCorrectly()
    {
        $this->mockSecurityContext->method('hasRole')->willReturnCallback(function ($role) {
            switch ($role) {
                case 'Neos.FluidAdaptor:Administrator':
                    return true;
                case 'Neos.FluidAdaptor:User':
                    return false;
            }
        });

        $this->mockViewHelper->method('renderThenChild')->willReturn(('true'));
        $this->mockViewHelper->method('renderElseChild')->willReturn(('false'));

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

    #[Test]
    public function viewHelperUsesSpecifiedAccountForCheck()
    {
        $mockAccount = $this->createMock(Account::class);
        $mockAccount->method('hasRole')->willReturnCallback(function (Role $role) {
            switch ($role->getIdentifier()) {
                case 'Neos.FluidAdaptor:Administrator':
                    return true;
            }
        });

        $this->mockViewHelper->method('renderThenChild')->willReturn(('true'));
        $this->mockViewHelper->method('renderElseChild')->willReturn(('false'));

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
