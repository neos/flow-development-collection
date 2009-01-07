<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security;

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
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 */

/**
 * This is the default session implementation of security ContextHolder.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class ContextHolderSession implements \F3\FLOW3\Security\ContextHolderInterface {

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory = NULL;

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager = NULL;

	/**
	 * @var \F3\FLOW3\Security\Authentication\ManagerInterface
	 */
	protected $authenticationManager = NULL;

	/**
	 * @var \F3\FLOW3\Session\SessionInterface The user session
	 */
	protected $session = NULL;

	/**
	 * Constructor.
	 *
	 * @param \F3\FLOW3\Session\SessionInterface $session An readily initialized session
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(\F3\FLOW3\Session\SessionInterface $session) {
		$this->session = $session;
	}

	/**
	 * Inject the object factory
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory The object factory
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Inject the object manager
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager The object manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Inject the authentication manager
	 *
	 * @param \F3\FLOW3\Security\Authentication\ManagerInterface $objectManager The authentication manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectAuthenticationManager(\F3\FLOW3\Security\Authentication\ManagerInterface $authenticationManager) {
		$this->authenticationManager = $authenticationManager;
	}

	/**
	 * Stores the current security context to the session.
	 *
	 * @param \F3\FLOW3\Security\Context $securityContext The current security context
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setContext(\F3\FLOW3\Security\Context $securityContext) {
		$this->session->putData('F3\FLOW3\Security\ContextHolderSession', $securityContext);
	}

	/**
	 * Returns the current \F3\FLOW3\Security\Context.
	 *
	 * @return \F3\FLOW3\Security\Context The current security context
	 * @throws \F3\FLOW3\Security\Exception\NoContextAvailable if no context is available (i.e. initializeContext() has not been called)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getContext() {
		$context = $this->session->getData('F3\FLOW3\Security\ContextHolderSession');

		if ($context instanceof \F3\FLOW3\Security\Context) {
			return $context;
		} else {
			throw new \F3\FLOW3\Security\Exception\NoContextAvailable('No context found in session, did you call initializeContext()?', 1225800610);
		}
	}

	/**
	 * Initializes the security context for the given request. It is loaded from the session.
	 *
	 * @param \F3\FLOW3\MVC\Request $request The request the context should be initialized for
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeContext(\F3\FLOW3\MVC\Request $request) {
		$context = $this->session->getData('F3\FLOW3\Security\ContextHolderSession');
		if (!($context instanceof \F3\FLOW3\Security\Context)) {
			$context =  $this->objectFactory->create('F3\FLOW3\Security\Context');
		}
		$context->setRequest($request);

		$this->authenticationManager->setSecurityContext($context);
		$managerTokens = $this->authenticationManager->getTokens();
		$sessionTokens = $context->getAuthenticationTokens();
		$mergedTokens = $this->mergeTokens($managerTokens, $sessionTokens);

		$this->updateTokens($mergedTokens);
		$context->setAuthenticationTokens($mergedTokens);

		$this->setContext($context);
	}

	/**
	 * Clears the current security context.
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function clearContext() {
		$this->setContext(NULL);
	}

	/**
	 * Merges the session and manager tokens. All manager tokens types will be in the result array
	 * If a specific type is found in the session this token replaces the one I(of the same type)
	 * given by the manager.
	 *
	 * @params array Array of tokens provided by the authentication manager
	 * @params array Array of tokens resotored from the session
	 * @return array Array of \F3\FLOW3\Security\Authentication\TokenInterface objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function mergeTokens($managerTokens, $sessionTokens) {
		$resultTokens = array();

		if (!is_array($managerTokens)) return $resultTokens;

		foreach ($managerTokens as $managerToken) {
			$noCorrespondingSessionTokenFound = TRUE;

			if (!is_array($sessionTokens)) continue;

			foreach ($sessionTokens as $sessionToken) {
				$managerTokenClass = get_class($managerToken);

				if ($sessionToken instanceof $managerTokenClass) {
					$resultTokens[] = $sessionToken;
					$noCorrespondingSessionTokenFound = FALSE;
				}
			}

			if ($noCorrespondingSessionTokenFound) $resultTokens[] = $managerToken;
		}

		return $resultTokens;
	}

	/**
	 * Updates the token credentials for all tokens in the given array.
	 *
	 * @param array Array of authentication tokens the credentials should be updated for
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo Remove manual DI. Should be handled by a session scope
	 */
	protected function updateTokens(array $tokens) {
		foreach ($tokens as $token) {
			$this->manuallyInjectDependenciesIntoUsernamePasswordToken($token);
			$token->updateCredentials();
		}
	}

	/**
	 * Manual dependency injection into UsernamePassword tokens until we have working session scope.
	 * Note: This is definitely a dirty hack
	 *
	 * @param \F3\FLOW3\Security\Authentication\TokenInterface $token The token we should inject some objects
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function manuallyInjectDependenciesIntoUsernamePasswordToken(\F3\FLOW3\Security\Authentication\TokenInterface $token) {
		if ($token instanceof \F3\FLOW3\Security\Authentication\Token\UsernamePassword) {
			$token->injectObjectFactory($this->objectFactory);
			$token->injectEnvironment($this->objectManager->getObject('F3\FLOW3\Utility\Environment'));
		}
	}
}

?>