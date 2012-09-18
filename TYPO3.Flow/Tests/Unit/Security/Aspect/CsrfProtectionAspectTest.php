<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the csrf protection aspect
 *
 */
class CsrfProtectionAspectTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * Arguments being passed to UriBuilder::build
	 * @var array
	 */
	protected $arguments = array(
		'@package' => 'Acme.MyPackage',
		'@subpackage' => 'Subpackage',
		'@controller' => 'Some',
		'@action' => 'index',
	);

	/**
	 * Expected Controller object name
	 * @var string
	 */
	protected $controllerObjectName = 'acme\\mypackage\\subpackage\\controller\\somecontroller';

	/**
	 * Arguments which have been set using UriBuilder::setArguments
	 * @var array
	 */
	protected $internalUriBuilderArguments = array(
		'some' => 'arg'
	);

	/**
	 * @var \TYPO3\FLOW3\Mvc\Routing\Router
	 */
	protected $mockRouter;

	/**
	 * Mock URI Builder
	 */
	protected $mockUriBuilder;

	/**
	 * Mock Joinpoint
	 */
	protected $mockJoinPoint;

	/**
	 * Mock ReflectionService
	 */
	protected $mockReflectionService;

	/**
	 * The System Under Test (SUT)
	 * @var \TYPO3\FLOW3\Security\Aspect\CsrfProtectionAspect
	 */
	protected $csrfProtectionAspect;

	/**
	 * Initialization method. Sets up all the complex dependencies.
	 *
	 * @return void
	 */
	public function setUp() {
		$this->mockRouter = $this->getMock('TYPO3\FLOW3\Mvc\Routing\Router');
		$this->mockRouter->expects($this->any())->method('getControllerObjectName')->will($this->returnValue($this->controllerObjectName));

		$this->mockUriBuilder = $this->getMock('TYPO3\FLOW3\Mvc\Routing\UriBuilder');
		$this->mockUriBuilder->expects($this->any())->method('getArguments')->will($this->returnValue($this->internalUriBuilderArguments));

		$mockAdviceChain = $this->getMock('TYPO3\FLOW3\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->any())->method('proceed')->will($this->returnValue(array()));

		$this->mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPoint', array(), array(), '', FALSE);
		$this->mockJoinPoint->expects($this->any())->method('getProxy')->will($this->returnValue($this->mockUriBuilder));
		$this->mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

		$this->mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService');

		$this->mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array('isMethodAnnotatedWith', 'hasMethod'));
		$this->mockReflectionService->expects($this->any())->method('hasMethod')->will($this->returnValue(TRUE));

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager
			->expects($this->any())
			->method('getCaseSensitiveObjectName')
			->with($this->controllerObjectName)
			->will($this->returnValue($this->controllerObjectName));

		$mockObjectManager
			->expects($this->any())
			->method('getClassNameByObjectName')
			->with($this->controllerObjectName)
			->will($this->returnValue($this->controllerObjectName));

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->any())->method('getCsrfProtectionToken')->will($this->returnValue('csrf-token'));

		$csrfProtectionAspect = $this->getAccessibleMock('TYPO3\FLOW3\Security\Aspect\CsrfProtectionAspect', array('dummy'));
		$csrfProtectionAspect->_set('objectManager', $mockObjectManager);
		$csrfProtectionAspect->_set('reflectionService', $this->mockReflectionService);
		$csrfProtectionAspect->_set('policyService', $this->mockPolicyService);
		$csrfProtectionAspect->_set('securityContext', $mockSecurityContext);
		$csrfProtectionAspect->_set('router', $this->mockRouter);

		$this->csrfProtectionAspect = $csrfProtectionAspect;
	}

	/**
	 * @test
	 */
	public function addCsrfTokenToUriDoesNothingIfTheTargetControllerActionIsTaggedWithSkipCsrfProtection() {
		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$this->csrfProtectionAspect->_set('authenticationManager', $mockAuthenticationManager);

		$this->expectThat_PolicyServiceHasPolicyEntry(TRUE);
		$this->expectThat_ActionIsTaggedWithSkipCsrfProtection(TRUE);

		$arguments = $this->csrfProtectionAspect->addCsrfTokenToUri($this->mockJoinPoint);

		$this->assertSame($arguments, array());
	}


	/**
	 * @test
	 */
	public function addCsrfTokenToUriDoesNothingIfNoOneIsAuthenticated() {
		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$this->csrfProtectionAspect->_set('authenticationManager', $mockAuthenticationManager);

		$this->expectThat_PolicyServiceHasPolicyEntry(FALSE);
		$arguments = $this->csrfProtectionAspect->addCsrfTokenToUri($this->mockJoinPoint);

		$this->assertSame($arguments, array());
	}

	/**
	 * @test
	 */
	public function addCsrfTokenToUriAddsAnCsrfTokenToTheUriArguentsIfTheTargetControllerActionIsMentionedInThePolicyAndNotTaggedWithSkipCsrfProtection() {
		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$this->csrfProtectionAspect->_set('authenticationManager', $mockAuthenticationManager);

		$this->expectThat_PolicyServiceHasPolicyEntry(TRUE);
		$this->expectThat_ActionIsTaggedWithSkipCsrfProtection(FALSE);

		$arguments = $this->csrfProtectionAspect->addCsrfTokenToUri($this->mockJoinPoint);

		$this->assertSame($arguments, array('__csrfToken' => 'csrf-token'));
	}

	/**
	 * Set the expectation that the Policy Service should have a given Policy Entry,
	 * or should not have a given policy entry.
	 *
	 * @param boolean $expected TRUE if the Policy Service should have the given Policy Entry, FALSE otherwise.
	 * @return void
	 */
	protected function expectThat_PolicyServiceHasPolicyEntry($expected) {
		$this->mockPolicyService
			->expects($this->any())
			->method('hasPolicyEntryForMethod')
			->with($this->controllerObjectName, $this->arguments['@action'] . 'Action')
			->will($this->returnValue($expected));
	}

	/**
	 * Set the expectation that an action should be tagged with @skipCsrfProtection or not.
	 *
	 * @param boolean $expected TRUE if the action should be tagged, FALSE if not.
	 * @return void
	 */
	protected function expectThat_ActionIsTaggedWithSkipCsrfProtection($expected) {
		$this->mockReflectionService
			->expects($this->any())
			->method('isMethodAnnotatedWith')
			->with($this->controllerObjectName, $this->arguments['@action'] . 'Action', 'TYPO3\FLOW3\Annotations\SkipCsrfProtection')
			->will($this->returnValue($expected));
	}
}
?>