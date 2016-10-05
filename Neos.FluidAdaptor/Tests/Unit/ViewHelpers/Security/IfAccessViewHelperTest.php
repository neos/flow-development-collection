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

use TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\FluidAdaptor\ViewHelpers\Security\IfAccessViewHelper;
use Neos\FluidAdaptor\ViewHelpers\ViewHelperBaseTestcase;

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

    public function setUp()
    {
        $this->mockPrivilegeManager = $this->createMock(\TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface::class);

        $this->ifAccessViewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Security\IfAccessViewHelper::class, array('renderThenChild', 'renderElseChild'));
        $this->inject($this->ifAccessViewHelper, 'privilegeManager', $this->mockPrivilegeManager);
    }

    /**
     * @test
     */
    public function viewHelperRendersThenIfHasAccessToPrivilegeTargetReturnsTrue()
    {
        $this->mockPrivilegeManager->expects($this->once())->method('isPrivilegeTargetGranted')->with('somePrivilegeTarget')->will($this->returnValue(true));
        $this->ifAccessViewHelper->expects($this->once())->method('renderThenChild')->will($this->returnValue('foo'));

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

        $actualResult = $this->ifAccessViewHelper->render('somePrivilegeTarget');
        $this->assertEquals('ElseViewHelperResults', $actualResult);
    }
}
