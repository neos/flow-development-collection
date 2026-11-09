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
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\FluidAdaptor\Core\Rendering\RenderingContext;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Security\IfAccessViewHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for IfAccessViewHelper
 *
 */
final class IfAccessViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var IfAccessViewHelper
     */
    protected $ifAccessViewHelper;

    /**
     * @var PrivilegeManagerInterface|MockObject
     */
    protected $mockPrivilegeManager;

    protected function setUp(): void
    {
        $this->mockPrivilegeManager = $this->createMock(PrivilegeManagerInterface::class);

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback(function ($objectName) {
            switch ($objectName) {
                case PrivilegeManagerInterface::class:
                    return $this->mockPrivilegeManager;
                    break;
            }
        });

        $renderingContext = $this->createMock(RenderingContext::class);
        $renderingContext->method('getObjectManager')->willReturn($objectManager);

        $this->ifAccessViewHelper = $this->getAccessibleMock(IfAccessViewHelper::class, ['renderThenChild', 'renderElseChild']);
        $this->inject($this->ifAccessViewHelper, 'renderingContext', $renderingContext);
    }

    #[Test]
    public function viewHelperRendersThenIfHasAccessToPrivilegeTargetReturnsTrue()
    {
        $this->mockPrivilegeManager->expects($this->once())->method('isPrivilegeTargetGranted')->with('somePrivilegeTarget')->willReturn((true));
        $this->ifAccessViewHelper->expects($this->once())->method('renderThenChild')->willReturn(('foo'));

        $arguments = [
            'privilegeTarget' => 'somePrivilegeTarget',
            'parameters' => []
        ];
        $this->ifAccessViewHelper->setArguments($arguments);
        $actualResult = $this->ifAccessViewHelper->render();
        self::assertEquals('foo', $actualResult);
    }

    #[Test]
    public function viewHelperRendersElseIfHasAccessToPrivilegeTargetReturnsFalse()
    {
        $this->mockPrivilegeManager->expects($this->once())->method('isPrivilegeTargetGranted')->with('somePrivilegeTarget')->willReturn((false));
        $this->ifAccessViewHelper->expects($this->once())->method('renderElseChild')->willReturn(('ElseViewHelperResults'));

        $arguments = [
            'privilegeTarget' => 'somePrivilegeTarget',
            'parameters' => []
        ];
        $this->ifAccessViewHelper->setArguments($arguments);
        $actualResult = $this->ifAccessViewHelper->render();
        self::assertEquals('ElseViewHelperResults', $actualResult);
    }
}
