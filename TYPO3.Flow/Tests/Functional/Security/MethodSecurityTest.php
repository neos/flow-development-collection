<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Functional\Security;

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
 * Testcase for method security
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class MethodSecurityTest extends \F3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @var \F3\FLOW3\Tests\Functional\Security\Fixtures\RestrictedController
	 */
	protected $restrictedController;

	/**
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setUp() {
		parent::setUp();
		$this->restrictedController = $this->objectManager->get('F3\FLOW3\Tests\Functional\Security\Fixtures\RestrictedController');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function publicActionIsGrantedForEverybody() {
		$this->restrictedController->publicAction();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function publicActionIsGrantedForCustomer() {
		$this->authenticateRoles(array('Customer'));
		$this->restrictedController->publicAction();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function publicActionIsGrantedForAdministrator() {
		$this->authenticateRoles(array('Administrator'));
		$this->restrictedController->publicAction();
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Security\Exception\AccessDeniedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function customerActionIsDeniedForEverybody() {
		$this->restrictedController->customerAction();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function customerActionIsGrantedForCustomer() {
		$this->authenticateRoles(array('Customer'));
		$this->restrictedController->customerAction();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function customerActionIsGrantedForAdministrator() {
		$this->authenticateRoles(array('Administrator'));
		$this->restrictedController->customerAction();
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Security\Exception\AccessDeniedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function adminActionIsDeniedForEverybody() {
		$this->restrictedController->adminAction();
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Security\Exception\AccessDeniedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function adminActionIsDeniedForCustomer() {
		$this->authenticateRoles(array('Customer'));
		$this->restrictedController->adminAction();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function adminActionIsGrantedForAdministrator() {
		$this->authenticateRoles(array('Administrator'));
		$this->restrictedController->adminAction();
	}
}
?>