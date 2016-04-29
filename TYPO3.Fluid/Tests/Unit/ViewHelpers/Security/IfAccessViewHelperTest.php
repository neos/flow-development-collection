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

use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use TYPO3\Fluid\Core\Rendering\RenderingContext;
use TYPO3\Fluid\ViewHelpers\Security\IfAccessViewHelper;
use TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

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
     * @var PrivilegeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPrivilegeManager;

    /**
     * @var RenderingContext
     */
    protected $renderingContextMock;

    public function setUp()
    {
        $this->mockPrivilegeManager = $this->getMockBuilder(\TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface::class)->getMock();
        $objectManagerMock = $this->getMock(ObjectManagerInterface::class);
        $objectManagerMock->expects(self::any())->method('get')->with(PrivilegeManagerInterface::class)->willReturn($this->mockPrivilegeManager);

        $this->renderingContextMock = $this->getMock(RenderingContext::class);
        $this->renderingContextMock->expects(self::any())->method('getObjectManager')->willReturn($objectManagerMock);

        $this->ifAccessViewHelper = $this->getAccessibleMock(\TYPO3\Fluid\ViewHelpers\Security\IfAccessViewHelper::class, array('renderThenChild', 'renderElseChild'));
        $this->ifAccessViewHelper->setRenderingContext($this->renderingContextMock);
    }

    /**
     * @test
     */
    public function viewHelperRendersThenIfHasAccessToPrivilegeTargetReturnsTrue()
    {
        $this->mockPrivilegeManager->expects($this->once())->method('isPrivilegeTargetGranted')->with('somePrivilegeTarget')->will($this->returnValue(true));
        $this->ifAccessViewHelper->expects($this->once())->method('renderThenChild')->willReturn('foo');

        $this->ifAccessViewHelper->_set('arguments', ['privilegeTarget' => 'somePrivilegeTarget', 'parameters' => []]);

        $actualResult = $this->ifAccessViewHelper->render('somePrivilegeTarget');
        $this->assertEquals('foo', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersElseIfHasAccessToPrivilegeTargetReturnsFalse()
    {
        $this->mockPrivilegeManager->expects($this->once())->method('isPrivilegeTargetGranted')->with('somePrivilegeTarget')->will($this->returnValue(false));
        $this->ifAccessViewHelper->expects($this->once())->method('renderElseChild')->will($this->returnValue('ElseViewHelperResults'));

        $this->ifAccessViewHelper->_set('arguments', ['privilegeTarget' => 'somePrivilegeTarget', 'parameters' => []]);

        $actualResult = $this->ifAccessViewHelper->render('somePrivilegeTarget');
        $this->assertEquals('ElseViewHelperResults', $actualResult);
    }
}
