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

use TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface;
use TYPO3\Fluid\ViewHelpers\Security\IfAccessViewHelper;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Testcase for IfAccessViewHelper
 *
 */
class IfAccessViewHelperTest extends \TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var AccessDecisionManagerInterface
     */
    protected $mockAccessDecisionManager;

    /**
     * @var IfAccessViewHelper
     */
    protected $ifAccessViewHelper;

    public function setUp()
    {
        $this->mockAccessDecisionManager = $this->getMockBuilder('TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface')->getMock();

        $this->ifAccessViewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Security\IfAccessViewHelper', array('renderThenChild', 'renderElseChild'));
        $this->inject($this->ifAccessViewHelper, 'accessDecisionManager', $this->mockAccessDecisionManager);
    }

    /**
     * @test
     */
    public function viewHelperRendersThenIfHasAccessToResourceReturnsTrue()
    {
        $this->mockAccessDecisionManager->expects($this->once())->method('hasAccessToResource')->with('someResource')->will($this->returnValue(true));
        $this->ifAccessViewHelper->expects($this->once())->method('renderThenChild')->will($this->returnValue('foo'));

        $actualResult = $this->ifAccessViewHelper->render('someResource');
        $this->assertEquals('foo', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersElseIfHasAccessToResourceReturnsFalse()
    {
        $this->mockAccessDecisionManager->expects($this->once())->method('hasAccessToResource')->with('someResource')->will($this->returnValue(false));
        $this->ifAccessViewHelper->expects($this->once())->method('renderElseChild')->will($this->returnValue('ElseViewHelperResults'));

        $actualResult = $this->ifAccessViewHelper->render('someResource');
        $this->assertEquals('ElseViewHelperResults', $actualResult);
    }
}
