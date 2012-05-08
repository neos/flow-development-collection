<?php
namespace TYPO3\FLOW3\Tests\Functional\Security;

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
 * Testcase for method security
 *
 */
class MethodSecurityTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @var \TYPO3\FLOW3\Tests\Functional\Security\Fixtures\Controller\RestrictedController
	 */
	protected $restrictedController;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->restrictedController = $this->objectManager->get('TYPO3\FLOW3\Tests\Functional\Security\Fixtures\Controller\RestrictedController');
	}

	/**
	 * @test
	 */
	public function publicActionIsGrantedForEverybody() {
		$this->restrictedController->publicAction();
	}

	/**
	 * @test
	 */
	public function publicActionIsGrantedForCustomer() {
		$this->authenticateRoles(array('Customer'));
		$this->restrictedController->publicAction();
	}

	/**
	 * @test
	 */
	public function publicActionIsGrantedForAdministrator() {
		$this->authenticateRoles(array('Administrator'));
		$this->restrictedController->publicAction();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Security\Exception\AuthenticationRequiredException
	 */
	public function customerActionIsDeniedForEverybody() {
		$this->restrictedController->customerAction();
	}

	/**
	 * @test
	 */
	public function customerActionIsGrantedForCustomer() {
		$this->authenticateRoles(array('Customer'));
		$this->restrictedController->customerAction();
	}

	/**
	 * @test
	 */
	public function customerActionIsGrantedForAdministrator() {
		$this->authenticateRoles(array('Administrator'));
		$this->restrictedController->customerAction();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Security\Exception\AuthenticationRequiredException
	 */
	public function adminActionIsDeniedForEverybody() {
		$this->restrictedController->adminAction();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Security\Exception\AccessDeniedException
	 */
	public function adminActionIsDeniedForCustomer() {
		$this->authenticateRoles(array('Customer'));
		$this->restrictedController->adminAction();
	}

	/**
	 * @test
	 */
	public function adminActionIsGrantedForAdministrator() {
		$this->authenticateRoles(array('Administrator'));
		$this->restrictedController->adminAction();
	}
}
?>