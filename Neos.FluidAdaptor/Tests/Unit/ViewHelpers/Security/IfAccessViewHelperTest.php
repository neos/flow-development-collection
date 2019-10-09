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
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\FluidAdaptor\ViewHelpers\Security\IfAccessViewHelper;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Testcase for IfAccessViewHelper
 *
 */
class IfAccessViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var IfAccessViewHelper
     */
    protected $ifAccessViewHelper;

    /**
     * @var PrivilegeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPrivilegeManager;

    protected function setUp(): void
    {
        $this->mockPrivilegeManager = $this->getMockBuilder(\Neos\Flow\Security\Authorization\PrivilegeManagerInterface::class)->disableOriginalConstructor()->getMock();

        $objectManager = $this->getMockBuilder(ObjectManagerInterface::class)->disableOriginalConstructor()->getMock();
        $objectManager->expects(self::any())->method('get')->willReturnCallback(function ($objectName) {
            switch ($objectName) {
                case PrivilegeManagerInterface::class:
                    return $this->mockPrivilegeManager;
                    break;
            }
        });

        $renderingContext = $this->getMockBuilder(RenderingContext::class)->disableOriginalConstructor()->getMock();
        $renderingContext->expects(self::any())->method('getObjectManager')->willReturn($objectManager);

        $this->ifAccessViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Security\IfAccessViewHelper::class, ['renderThenChild', 'renderElseChild']);
        $this->inject($this->ifAccessViewHelper, 'renderingContext', $renderingContext);
    }

    /**
     * @test
     */
    public function viewHelperRendersThenIfHasAccessToPrivilegeTargetReturnsTrue()
    {
        $this->mockPrivilegeManager->expects(self::once())->method('isPrivilegeTargetGranted')->with('somePrivilegeTarget')->will(self::returnValue(true));
        $this->ifAccessViewHelper->expects(self::once())->method('renderThenChild')->will(self::returnValue('foo'));

        $arguments = [
            'privilegeTarget' => 'somePrivilegeTarget',
            'parameters' => []
        ];
        $this->ifAccessViewHelper->setArguments($arguments);
        $actualResult = $this->ifAccessViewHelper->render();
        self::assertEquals('foo', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersElseIfHasAccessToPrivilegeTargetReturnsFalse()
    {
        $this->mockPrivilegeManager->expects(self::once())->method('isPrivilegeTargetGranted')->with('somePrivilegeTarget')->will(self::returnValue(false));
        $this->ifAccessViewHelper->expects(self::once())->method('renderElseChild')->will(self::returnValue('ElseViewHelperResults'));

        $arguments = [
            'privilegeTarget' => 'somePrivilegeTarget',
            'parameters' => []
        ];
        $this->ifAccessViewHelper->setArguments($arguments);
        $actualResult = $this->ifAccessViewHelper->render();
        self::assertEquals('ElseViewHelperResults', $actualResult);
    }
}
