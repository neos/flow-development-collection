<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Security\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the csrf protection aspect
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class CsrfProtectionAspectTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addCsrfTokenToUriDoesNothingIfTheTargetControllerActionIsTaggedWithSkipCsrfProtection() {
		$arguments = array(
			'@package' => 'MyPackage',
			'@subpackage' => 'Subpackage',
			'@controller' => 'Some',
			'@action' => 'index',
		);
		$controllerObjectName = 'f3\\mypackage\\subpackage\\controller\\somecontroller';

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));
		$mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $arguments['@action'] . 'Action', 'skipCsrfProtection')->will($this->returnValue(TRUE));

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForMethod')->with($controllerObjectName, $arguments['@action'] . 'Action')->will($this->returnValue(TRUE));

		$mockUriBuilder = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriBuilder');
		$mockUriBuilder->expects($this->never())->method('setArguments');
		$mockUriBuilder->expects($this->once())->method('getArguments')->will($this->returnValue($arguments));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPoint', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockUriBuilder));

		$mockCsrfProtectionAspect = $this->getMock('F3\FLOW3\Security\Aspect\CsrfProtectionAspect', array('dummy'));
		$mockCsrfProtectionAspect->injectObjectManager($mockObjectManager);
		$mockCsrfProtectionAspect->injectReflectionService($mockReflectionService);
		$mockCsrfProtectionAspect->injectPolicyService($mockPolicyService);

		$mockCsrfProtectionAspect->addCsrfTokenToUri($mockJoinPoint);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addCsrfTokenToUriDoesNothingIfTheTargetControllerActionIsNotMentionedInThePolicy() {
		$arguments = array(
			'@package' => 'MyPackage',
			'@subpackage' => 'Subpackage',
			'@controller' => 'Some',
			'@action' => 'index',
		);
		$controllerObjectName = 'f3\\mypackage\\subpackage\\controller\\somecontroller';

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));
		$mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForMethod')->with($controllerObjectName, $arguments['@action'] . 'Action')->will($this->returnValue(FALSE));

		$mockUriBuilder = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriBuilder');
		$mockUriBuilder->expects($this->never())->method('setArguments');
		$mockUriBuilder->expects($this->once())->method('getArguments')->will($this->returnValue($arguments));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPoint', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockUriBuilder));

		$mockCsrfProtectionAspect = $this->getMock('F3\FLOW3\Security\Aspect\CsrfProtectionAspect', array('dummy'));
		$mockCsrfProtectionAspect->injectObjectManager($mockObjectManager);
		$mockCsrfProtectionAspect->injectPolicyService($mockPolicyService);

		$mockCsrfProtectionAspect->addCsrfTokenToUri($mockJoinPoint);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addCsrfTokenToUriAddsAnCsrfTokenToTheUriArguentsIfTheTargetControllerActionIsMentionedInThePolicyAndNotTaggedWithSkipCsrfProtection() {
		$arguments = array(
			'@package' => 'MyPackage',
			'@subpackage' => 'Subpackage',
			'@controller' => 'Some',
			'@action' => 'index',
		);
		$controllerObjectName = 'f3\\mypackage\\subpackage\\controller\\somecontroller';

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));
		$mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $arguments['@action'] . 'Action', 'skipCsrfProtection')->will($this->returnValue(FALSE));

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->once())->method('hasPolicyEntryForMethod')->with($controllerObjectName, $arguments['@action'] . 'Action')->will($this->returnValue(TRUE));

		$expectedArguments = array(
			'@package' => 'MyPackage',
			'@subpackage' => 'Subpackage',
			'@controller' => 'Some',
			'@action' => 'index',
			'FLOW3-CSRF-TOKEN' => 'csrf-token'
		);

		$mockUriBuilder = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriBuilder');
		$mockUriBuilder->expects($this->once())->method('setArguments')->with($expectedArguments);
		$mockUriBuilder->expects($this->once())->method('getArguments')->will($this->returnValue($arguments));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPoint', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($mockUriBuilder));

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getCsrfProtectionToken')->will($this->returnValue('csrf-token'));

		$mockCsrfProtectionAspect = $this->getMock('F3\FLOW3\Security\Aspect\CsrfProtectionAspect', array('dummy'));
		$mockCsrfProtectionAspect->injectObjectManager($mockObjectManager);
		$mockCsrfProtectionAspect->injectReflectionService($mockReflectionService);
		$mockCsrfProtectionAspect->injectPolicyService($mockPolicyService);
		$mockCsrfProtectionAspect->injectSecurityContext($mockSecurityContext);

		$mockCsrfProtectionAspect->addCsrfTokenToUri($mockJoinPoint);
	}
}
?>