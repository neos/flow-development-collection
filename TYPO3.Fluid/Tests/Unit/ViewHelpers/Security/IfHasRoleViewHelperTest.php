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

use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Security\Policy\PolicyService;
use TYPO3\Flow\Security\Policy\Role;
use TYPO3\Fluid\ViewHelpers\Security\IfHasRoleViewHelper;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Testcase for IfHasRoleViewHelper
 *
 */
class IfHasRoleViewHelperTest extends \TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * Create a mock controllerContext
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockControllerContext()
    {
        $httpRequest = \TYPO3\Flow\Http\Request::create(new \TYPO3\Flow\Http\Uri('http://robertlemke.com/blog'));
        $mockRequest = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array(), array($httpRequest));
        $mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('TYPO3.Fluid'));

        $mockControllerContext = $this->getMock('TYPO3\Flow\Mvc\Controller\ControllerContext', array('getRequest'), array(), '', false);
        $mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

        return $mockControllerContext;
    }

    /**
     * @test
     */
    public function viewHelperRendersThenPartIfHasRoleReturnsTrue()
    {
        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('hasRole')->with('TYPO3.Fluid:someGA')->will($this->returnValue(true));

        $mockViewHelper = $this->getMock('TYPO3\Fluid\ViewHelpers\Security\IfHasRoleViewHelper', array('renderThenChild', 'hasAccessToResource'));
        $this->inject($mockViewHelper, 'securityContext', $mockSecurityContext);
        $this->inject($mockViewHelper, 'controllerContext', $this->getMockControllerContext());
        $mockViewHelper->expects($this->once())->method('renderThenChild')->will($this->returnValue('foo'));

        $actualResult = $mockViewHelper->render('someGA');
        $this->assertEquals('foo', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperHandlesPackageKeyAttributeCorrectly()
    {
        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->any())->method('hasRole')->will($this->returnCallback(function ($role) {
            switch ($role) {
                case 'TYPO3.Fluid:Administrator':
                    return true;
                case 'TYPO3.Fluid:User':
                    return false;
                case 'Everybody':
                    return true;
                default:
                    return false;
            }
        }));

        $mockViewHelper = $this->getMock('TYPO3\Fluid\ViewHelpers\Security\IfHasRoleViewHelper', array('renderThenChild', 'renderElseChild', 'hasAccessToResource'));
        $this->inject($mockViewHelper, 'securityContext', $mockSecurityContext);
        $this->inject($mockViewHelper, 'controllerContext', $this->getMockControllerContext());
        $mockViewHelper->expects($this->any())->method('renderThenChild')->will($this->returnValue('true'));
        $mockViewHelper->expects($this->any())->method('renderElseChild')->will($this->returnValue('false'));

        $actualResult = $mockViewHelper->render('TYPO3.Fluid:Administrator');
        $this->assertEquals('true', $actualResult, 'Full role identifier in role argument is accepted');

        $actualResult = $mockViewHelper->render('Administrator');
        $this->assertEquals('true', $actualResult, 'Packagekey from controllerContext is automatically prepended if packageKey is absent');

        $actualResult = $mockViewHelper->render('Everybody');
        $this->assertEquals('true', $actualResult);

        $actualResult = $mockViewHelper->render('Administrator', 'TYPO3.Fluid');
        $this->assertEquals('true', $actualResult);

        $actualResult = $mockViewHelper->render('User', 'TYPO3.Fluid');
        $this->assertEquals('false', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersElsePartIfConditionIsFalse()
    {
        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('hasRole')->with('TYPO3.Fluid:someGA')->will($this->returnValue(false));

        $mockViewHelper = $this->getMock('TYPO3\Fluid\ViewHelpers\Security\IfHasRoleViewHelper', array('renderElseChild'));
        $mockViewHelper->expects($this->once())->method('renderElseChild')->will($this->returnValue('ElseViewHelperResults'));
        $this->inject($mockViewHelper, 'securityContext', $mockSecurityContext);
        $this->inject($mockViewHelper, 'controllerContext', $this->getMockControllerContext());

        $actualResult = $mockViewHelper->render('someGA');
        $this->assertEquals('ElseViewHelperResults', $actualResult);
    }

    /**
     * signature: $roleIdentifier
     */
    public function systemRolesDataProvider()
    {
        $policyService = new PolicyService();
        $systemRoles = ObjectAccess::getProperty($policyService, 'systemRoles', true);
        $systemRoleNames = array();
        /** @var $systemRole Role */
        foreach ($systemRoles as $systemRole) {
            $systemRoleNames[] = array($systemRole->getIdentifier());
        }
        return $systemRoleNames;
    }

    /**
     * @test
     * @dataProvider systemRolesDataProvider
     */
    public function viewHelperDoesntMagicallyAugmentRoleIdentifierForSystemRole($roleIdentifier)
    {
        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('hasRole')->with($roleIdentifier);
        $viewHelper = new IfHasRoleViewHelper;
        $this->inject($viewHelper, 'securityContext', $mockSecurityContext);
        $viewHelper->render($roleIdentifier);
    }

    /**
     * @test
     * @dataProvider systemRolesDataProvider
     */
    public function viewHelperRendersThenChildForSystemRoles($roleIdentifier)
    {
        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockSecurityContext->expects($this->once())->method('hasRole')->will($this->returnValue(true));

        $mockViewHelper = $this->getMock('TYPO3\Fluid\ViewHelpers\Security\IfHasRoleViewHelper', array('renderThenChild'));
        $mockViewHelper->expects($this->once())->method('renderThenChild')->will($this->returnValue('ThenViewHelperResults'));
        $this->inject($mockViewHelper, 'securityContext', $mockSecurityContext);
        $this->assertEquals('ThenViewHelperResults', $mockViewHelper->render($roleIdentifier));
    }
}
