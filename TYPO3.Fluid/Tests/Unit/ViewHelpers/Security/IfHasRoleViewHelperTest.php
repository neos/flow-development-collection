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

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Policy\PolicyService;
use TYPO3\Flow\Security\Policy\Role;
use TYPO3\Fluid\Core\Rendering\RenderingContext;
use TYPO3\Fluid\ViewHelpers\Security\IfHasRoleViewHelper;
use TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Test case for IfHasRoleViewHelper
 *
 */
class IfHasRoleViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var PolicyService
     */
    protected $mockPolicyService;

    /**
     * @var Context
     */
    protected $mockSecurityContext;

    /**
     * @var RenderingContext
     */
    protected $mockRenderingContext;

    /**
     * Test setup
     */
    public function setUp()
    {
        parent::setUp();
        $this->request->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('Acme.Demo'));

        $this->mockPolicyService = $this->getMock(PolicyService::class, [], [], '', false);
        $this->mockSecurityContext = $this->getMock(\TYPO3\Flow\Security\Context::class, [], [], '', false);

        $mockObjectManager = $this->getMock(ObjectManagerInterface::class, [], [], '', false);
        $mockObjectManager->expects(self::any())->method('get')->willReturnCallback(function ($objectName) {
            switch ($objectName) {
                case \TYPO3\Flow\Security\Context::class:
                    return $this->mockSecurityContext;
                case PolicyService::class:
                    return $this->mockPolicyService;
            }
        });

        $this->renderingContext->injectObjectManager($mockObjectManager);
    }

    /**
     * @test
     */
    public function viewHelperRendersThenPartIfHasRoleReturnsTrue()
    {
        $role = new Role('Acme.Demo:SomeRole');

        $this->mockSecurityContext->expects($this->once())->method('hasRole')->with('Acme.Demo:SomeRole')->will($this->returnValue(true));
        $this->mockPolicyService->expects($this->once())->method('getRole')->with('Acme.Demo:SomeRole')->will($this->returnValue($role));

        $mockViewHelper = $this->getAccessibleMock(\TYPO3\Fluid\ViewHelpers\Security\IfHasRoleViewHelper::class, array('renderThenChild'));
        $mockViewHelper->setRenderingContext($this->renderingContext);

        $mockViewHelper->_set('arguments', ['role' => 'SomeRole']);

        $mockViewHelper->expects($this->once())->method('renderThenChild')->will($this->returnValue('then-child'));

        /** @var IfHasRoleViewHelper $mockViewHelper */
        $actualResult = $mockViewHelper->render('SomeRole');
        $this->assertEquals('then-child', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperHandlesPackageKeyAttributeCorrectly()
    {
        $this->mockSecurityContext->expects($this->any())->method('hasRole')->will($this->returnCallback(function ($role) {
            switch ($role) {
                case 'TYPO3.Fluid:Administrator':
                    return true;
                case 'TYPO3.Fluid:User':
                    return false;
            }
        }));

        $mockViewHelper = $this->getAccessibleMock(\TYPO3\Fluid\ViewHelpers\Security\IfHasRoleViewHelper::class, ['renderThenChild', 'renderElseChild']);
        $mockViewHelper->setRenderingContext($this->renderingContext);

        $mockViewHelper->expects($this->any())->method('renderThenChild')->will($this->returnValue('true'));
        $mockViewHelper->expects($this->any())->method('renderElseChild')->will($this->returnValue('false'));

        $mockViewHelper->_set('arguments', ['role' => new Role('TYPO3.Fluid:Administrator')]);

        $actualResult = $mockViewHelper->render(new Role('TYPO3.Fluid:Administrator'));
        $this->assertEquals('true', $actualResult, 'Full role identifier in role argument is accepted');

        $mockViewHelper->_set('arguments', ['role' => new Role('TYPO3.Fluid:User'), 'package' => 'TYPO3.Fluid']);

        $actualResult = $mockViewHelper->render(new Role('TYPO3.Fluid:User'), 'TYPO3.Fluid');
        $this->assertEquals('false', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesSpecifiedAccountForCheck()
    {
        $mockAccount = $this->getMock(\TYPO3\Flow\Security\Account::class);
        $mockAccount->expects($this->any())->method('hasRole')->will($this->returnCallback(function (Role $role) {
            switch ($role->getIdentifier()) {
                case 'TYPO3.Fluid:Administrator':
                    return true;
            }
        }));

        $mockViewHelper = $this->getAccessibleMock(\TYPO3\Fluid\ViewHelpers\Security\IfHasRoleViewHelper::class, [
            'renderThenChild',
            'renderElseChild'
        ]);
        $mockViewHelper->setRenderingContext($this->renderingContext);

        $mockViewHelper->expects($this->any())->method('renderThenChild')->will($this->returnValue('true'));
        $mockViewHelper->expects($this->any())->method('renderElseChild')->will($this->returnValue('false'));

        $mockViewHelper->_set('arguments', ['role' => new Role('TYPO3.Fluid:Administrator'), 'account' => $mockAccount]);

        /** @var IfHasRoleViewHelper $mockViewHelper */
        $actualResult = $mockViewHelper->render(new Role('TYPO3.Fluid:Administrator'), null, $mockAccount);
        $this->assertEquals('true', $actualResult, 'Full role identifier in role argument is accepted');
    }
}
