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
	 * Mock URI Builder
	 */
	protected $mockUriBuilder;

	/**
	 * Mock Joinpoint
	 */
	protected $mockJoinPoint;

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
		$this->mockUriBuilder = $this->getMock('TYPO3\FLOW3\MVC\Web\Routing\UriBuilder');

		$this->mockUriBuilder->expects($this->any())->method('getArguments')->will($this->returnValue($this->internalUriBuilderArguments));

		$this->mockJoinPoint = $this->getMock('TYPO3\FLOW3\AOP\JoinPoint', array(), array(), '', FALSE);
		$this->mockJoinPoint->expects($this->once())->method('getProxy')->will($this->returnValue($this->mockUriBuilder));
		$this->mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('arguments')->will($this->returnValue($this->arguments));

		$this->mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService');

		$this->mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array('isMethodAnnotatedWith'));

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager
			->expects($this->once())
			->method('getCaseSensitiveObjectName')
			->with($this->controllerObjectName)
			->will($this->returnValue($this->controllerObjectName));

		$mockObjectManager
			->expects($this->once())
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

		$this->csrfProtectionAspect = $csrfProtectionAspect;
	}

	/**
	 * @test
	 * @category unit
	 */
	public function addCsrfTokenToUriDoesNothingIfTheTargetControllerActionIsTaggedWithSkipCsrfProtection() {
		$this->expectThat_PolicyServiceHasPolicyEntry(TRUE);
		$this->expectThat_ActionIsTaggedWithSkipCsrfProtection(TRUE);

		$this->assertThat_CsrfTokenIsNotAddedToArguments();

		$this->csrfProtectionAspect->addCsrfTokenToUri($this->mockJoinPoint);
	}


	/**
	 * @test
	 * @category unit
	 */
	public function addCsrfTokenToUriDoesNothingIfTheTargetControllerActionIsNotMentionedInThePolicy() {
		$this->expectThat_PolicyServiceHasPolicyEntry(FALSE);

		$this->assertThat_CsrfTokenIsNotAddedToArguments();

		$this->csrfProtectionAspect->addCsrfTokenToUri($this->mockJoinPoint);
	}

	/**
	 * @test
	 * @category unit
	 */
	public function addCsrfTokenToUriAddsAnCsrfTokenToTheUriArguentsIfTheTargetControllerActionIsMentionedInThePolicyAndNotTaggedWithSkipCsrfProtection() {
		$this->expectThat_PolicyServiceHasPolicyEntry(TRUE);
		$this->expectThat_ActionIsTaggedWithSkipCsrfProtection(FALSE);

		$this->assertThat_CsrfTokenIsAddedToArguments();

		$this->csrfProtectionAspect->addCsrfTokenToUri($this->mockJoinPoint);
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
			->expects($this->once())
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

	/**
	 * Assert that the CSRF token is not added to the arguments
	 *
	 * @return void
	 */
	protected function assertThat_CsrfTokenIsNotAddedToArguments() {
		$this->mockUriBuilder->expects($this->never())->method('setArguments');
	}

	/**
	 * Assert that the CSRF token is added to the arguments
	 *
	 * @return void
	 */
	protected function assertThat_CsrfTokenIsAddedToArguments() {
		$expected = $this->internalUriBuilderArguments;
		$expected['__csrfToken'] = 'csrf-token';
		$this->mockUriBuilder->expects($this->once())->method('setArguments')->with($expected);
	}
}
?>