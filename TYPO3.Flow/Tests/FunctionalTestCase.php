<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests;

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
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A base test case for functional tests
 *
 * Subclass this base class if you want to take advantage of the framework
 * capabilities, for example are in need of the object manager.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
abstract class FunctionalTestCase extends \F3\FLOW3\Tests\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Core\Bootstrap
	 */
	protected static $flow3;

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = FALSE;

	/**
	 * @var \F3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @var boolean
	 */
	protected $testablePersistenceEnabled = FALSE;

	/**
	 * @var \F3\FLOW3\Persistence\PersistenceManager
	 */
	protected $persistenceManager;

	/**
	 * @var \F3\FLOW3\Persistence\Session
	 */
	protected $persistenceSession;

	/**
	 * @var \F3\FLOW3\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * @var \F3\FLOW3\Tests\Functional\Security\Authentication\Provider\TestingProvider
	 */
	protected $testingProvider;

	/**
	 * Initialize FLOW3
	 */
	public static function setUpBeforeClass() {
		if (!self::$flow3) {
			if (!isset($_SERVER['FLOW3_ROOTPATH'])) {
				exit('The environment variable FLOW3_ROOTPATH must be defined in order to run functional tests.');
			}
			require_once($_SERVER['FLOW3_ROOTPATH'] . 'Packages/Framework/FLOW3/Classes/Core/Bootstrap.php');

			\F3\FLOW3\Core\Bootstrap::defineConstants();

			self::$flow3 = new \F3\FLOW3\Core\Bootstrap('Testing');
			self::$flow3->initialize();
		}
	}

	/**
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function runBare() {
		$this->objectManager = self::$flow3->getObjectManager();
		parent::runBare();
	}

	/**
	 * Enables security tests for this testcase
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function enableTestableSecurity() {
		$this->testableSecurityEnabled = TRUE;
	}

	/**
	 * Enables persistence tests for this testcase
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function enableTestablePersistence() {
		$this->testablePersistenceEnabled = TRUE;
	}

	/**
	 * Sets up test requirements depending on the enabled tests
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setUp() {
		if ($this->testableSecurityEnabled === TRUE) {
			$this->setupSecurity();
		}
		if ($this->testablePersistenceEnabled === TRUE) {
			$this->setupPersistence();
		}
	}

	/**
	 * Sets up security test requirements
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function setupSecurity() {
		$this->objectManager->get('F3\FLOW3\Security\Authorization\AccessDecisionManagerInterface');

		$this->accessDecisionManager = $this->objectManager->get('F3\FLOW3\Security\Authorization\AccessDecisionManagerInterface');
		$this->accessDecisionManager->setOverrideDecision(NULL);

		$this->testingProvider = $this->objectManager->get('F3\FLOW3\Security\Authentication\Provider\TestingProvider');
		$this->testingProvider->setName('DefaultProvider');

		$this->securityContext = $this->objectManager->get('F3\FLOW3\Security\Context');
		$request = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$this->securityContext->initialize($request);
	}

	/**
	 * Sets up persistence test requirements
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function setupPersistence() {
		$this->persistenceManager = $this->objectManager->get('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$this->persistenceManager->initialize();
		$this->persistenceSession = $this->objectManager->get('F3\FLOW3\Persistence\Session');
	}

	/**
	 * Authenticate the given role names for the current test
	 *
	 * @param array $roleNames
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function authenticateRoles($roleNames) {
		$account = $this->objectManager->create('F3\FLOW3\Security\Account');
		$roles = array();
		foreach ($roleNames as $roleName) {
			$roles[] = $this->objectManager->create('F3\FLOW3\Security\Policy\Role', $roleName);
		}
		$account->setRoles($roles);

		$this->testingProvider->setAuthenticationStatus(\F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);
		$this->testingProvider->setAccount($account);

		$request = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$this->securityContext->initialize($request);
	}

	/**
	 * Disables authorization for the current test
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function disableAuthorization() {
		$this->accessDecisionManager->setOverrideDecision(TRUE);
	}

	/**
	 * Tears down test requirements depending on the enabled tests
	 *
	 * Note: tearDown() is also called if an exception occurred in one of the tests. If the problem is caused by
	 *       some security or persistence related part of FLOW3, the error might be hard to track because their
	 *       specialized tearDown() methods might cause fatal errors. In those cases just output the original
	 *       exception message by adding an echo($this->statusMessage) as the first line of this method.
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function tearDown() {
		if ($this->testableSecurityEnabled === TRUE) {
			$this->tearDownSecurity();
		}
		if ($this->testablePersistenceEnabled === TRUE) {
			$this->tearDownPersistence();
		}
	}

	/**
	 * Resets security test requirements
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function tearDownSecurity() {
		$this->accessDecisionManager->reset();
		$this->testingProvider->reset();
	}

	/**
	 * Resets persistence test requirements
	 *
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	protected function tearDownPersistence() {
		$this->persistenceManager->persistAll();
		$this->persistenceSession->destroy();
	}

}
?>