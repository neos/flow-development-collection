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

use Neos\FluidAdaptor\Core\Rendering\RenderingContext;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
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
     * @var IfHasRoleViewHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockViewHelper;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSecurityContext;

    /**
     * @var PolicyService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPolicyService;

    public function setUp()
    {
        parent::setUp();
        $this->mockViewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Security\IfHasRoleViewHelper::class)->setMethods([
            'renderThenChild',
            'renderElseChild'
        ])->getMock();

        $this->mockSecurityContext = $this->getMockBuilder(\Neos\Flow\Security\Context::class)->disableOriginalConstructor()->getMock();

        $this->mockPolicyService = $this->getMockBuilder(\Neos\Flow\Security\Policy\PolicyService::class)->disableOriginalConstructor()->getMock();

        $objectManager = $this->getMockBuilder(ObjectManagerInterface::class)->disableOriginalConstructor()->getMock();
        $objectManager->expects($this->any())->method('get')->willReturnCallback(function ($objectName) {
            switch ($objectName) {
                case Context::class:
                    return $this->mockSecurityContext;
                    break;
                case PolicyService::class:
                    return $this->mockPolicyService;
                    break;
            }
        });

        $renderingContext = $this->getMockBuilder(RenderingContext::class)->disableOriginalConstructor()->getMock();
        $renderingContext->expects($this->any())->method('getObjectManager')->willReturn($objectManager);
        $renderingContext->expects($this->any())->method('getControllerContext')->willReturn($this->getMockControllerContext());

        $this->inject($this->mockViewHelper, 'renderingContext', $renderingContext);
    }

    /**
     * Create a mock controllerContext
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockControllerContext()
    {
        $httpRequest = Request::create(new Uri('http://robertlemke.com/blog'));
        $mockRequest = $this->getMockBuilder(\Neos\Flow\Mvc\ActionRequest::class)->setConstructorArgs(array($httpRequest))->getMock();
        $mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('Acme.Demo'));

        $mockControllerContext = $this->getMockBuilder(\Neos\Flow\Mvc\Controller\ControllerContext::class)->setMethods(array('getRequest'))->disableOriginalConstructor()->getMock();
        $mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

        return $mockControllerContext;
    }

    /**
     * @test
     */
    public function viewHelperRendersThenPartIfHasRoleReturnsTrue()
    {
        $role = new Role('Acme.Demo:SomeRole');

        $this->mockSecurityContext->expects($this->once())->method('hasRole')->with('Acme.Demo:SomeRole')->will($this->returnValue(true));
        $this->mockPolicyService->expects($this->once())->method('getRole')->with('Acme.Demo:SomeRole')->will($this->returnValue($role));

        $this->mockViewHelper->expects($this->once())->method('renderThenChild')->will($this->returnValue('then-child'));

        $arguments = [
            'role' => 'SomeRole',
            'account' => null
        ];
        $this->mockViewHelper->setArguments($arguments);
        $actualResult = $this->mockViewHelper->render();
        $this->assertEquals('then-child', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperHandlesPackageKeyAttributeCorrectly()
    {
        $this->mockSecurityContext->expects($this->any())->method('hasRole')->will($this->returnCallback(function ($role) {
            switch ($role) {
                case 'Neos.FluidAdaptor:Administrator':
                    return true;
                case 'Neos.FluidAdaptor:User':
                    return false;
            }
        }));

        $this->mockViewHelper->expects($this->any())->method('renderThenChild')->will($this->returnValue('true'));
        $this->mockViewHelper->expects($this->any())->method('renderElseChild')->will($this->returnValue('false'));

        $arguments = [
            'role' => new Role('Neos.FluidAdaptor:Administrator'),
            'account' => null
        ];
        $this->mockViewHelper->setArguments($arguments);
        $actualResult = $this->mockViewHelper->render();
        $this->assertEquals('true', $actualResult, 'Full role identifier in role argument is accepted');

        $arguments = [
            'role' => new Role('Neos.FluidAdaptor:User'),
            'packageKey' => 'Neos.FluidAdaptor',
            'account' => null
        ];
        $this->mockViewHelper->setArguments($arguments);
        $actualResult = $this->mockViewHelper->render();
        $this->assertEquals('false', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesSpecifiedAccountForCheck()
    {
        $mockAccount = $this->createMock(\Neos\Flow\Security\Account::class);
        $mockAccount->expects($this->any())->method('hasRole')->will($this->returnCallback(function (Role $role) {
            switch ($role->getIdentifier()) {
                case 'Neos.FluidAdaptor:Administrator':
                    return true;
            }
        }));

        $this->mockViewHelper->expects($this->any())->method('renderThenChild')->will($this->returnValue('true'));
        $this->mockViewHelper->expects($this->any())->method('renderElseChild')->will($this->returnValue('false'));

        $arguments = [
            'role' => new Role('Neos.FluidAdaptor:Administrator'),
            'packageKey' => null,
            'account' => $mockAccount
        ];
        $this->mockViewHelper->setArguments($arguments);
        $actualResult = $this->mockViewHelper->render();
        $this->assertEquals('true', $actualResult, 'Full role identifier in role argument is accepted');
    }
}
