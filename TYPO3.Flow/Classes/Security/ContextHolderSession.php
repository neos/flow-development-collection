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
 * This is the default session implementation of security ContextHolder.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope session
 */
class ContextHolderSession implements \F3\FLOW3\Security\ContextHolderInterface {

	/**
	 * The current security context
	 * @var F3\FLOW3\Security\Context
	 */
	protected $context;

	/**
	 * @var F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory = NULL;

	/**
	 * @var F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager = NULL;

	/**
	 * @var F3\FLOW3\Security\Authentication\ManagerInterface
	 */
	protected $authenticationManager = NULL;

	/**
	 * Inject the object factory
	 *
	 * @param F3\FLOW3\Object\FactoryInterface $objectFactory The object factory
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Inject the object manager
	 *
	 * @param F3\FLOW3\Object\ManagerInterface $objectManager The object manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Inject the authentication manager
	 *
	 * @param F3\FLOW3\Security\Authentication\ManagerInterface $authenticationManager The authentication manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectAuthenticationManager(\F3\FLOW3\Security\Authentication\ManagerInterface $authenticationManager) {
		$this->authenticationManager = $authenticationManager;
	}

	/**
	 * Sets the given security context.
	 *
	 * @param F3\FLOW3\Security\Context $securityContext The current security context
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setContext(\F3\FLOW3\Security\Context $securityContext) {
		$this->context = $securityContext;
	}

	/**
	 * Returns the current \F3\FLOW3\Security\Context.
	 *
	 * @return F3\FLOW3\Security\Context The current security context
	 * @throws F3\FLOW3\Security\Exception\NoContextAvailable if no context is available (i.e. initializeContext() has not been called)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getContext() {
		if ($this->context instanceof \F3\FLOW3\Security\Context) {
			return $this->context;
		} else {
			throw new \F3\FLOW3\Security\Exception\NoContextAvailable('No context found in session, did you call initializeContext()?', 1225800610);
		}
	}

	/**
	 * Initializes the security context for the given request. It is loaded from the session.
	 *
	 * @param F3\FLOW3\MVC\RequestInterface $request The request the context should be initialized for
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeContext(\F3\FLOW3\MVC\RequestInterface $request) {
		if (!($this->context instanceof \F3\FLOW3\Security\Context)) {
			$this->context = $this->objectFactory->create('F3\FLOW3\Security\Context');
		}
		$this->context->setRequest($request);

		$this->authenticationManager->setSecurityContext($this->context);
		$managerTokens = $this->filterInactiveTokens($this->authenticationManager->getTokens(), $request);
		$sessionTokens = $this->context->getAuthenticationTokens();

		$mergedTokens = $this->mergeTokens($managerTokens, $sessionTokens);

		$this->updateTokens($mergedTokens);
		$this->context->setAuthenticationTokens($mergedTokens);
	}

	/**
	 * Clears the current security context.
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function clearContext() {
		$this->context = NULL;
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
	 * Filters all tokens that don't match for the given request.
	 *
	 * @param array $tokens The token array to be filtered
	 * @param F3\FLOW3\MVC\RequestInterface $request The request object
	 * @return array The filtered token array
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function filterInactiveTokens(array $tokens, \F3\FLOW3\MVC\RequestInterface $request) {
		$activeTokens = array();

		foreach ($tokens as $token) {
			if ($token->hasRequestPatterns()) {
				$requestPatterns = $token->getRequestPatterns();
				$tokenIsActive = TRUE;

				foreach ($requestPatterns as $requestPattern) {
					if ($requestPattern->canMatch($request)) {
						$tokenIsActive &= $requestPattern->matchRequest($request);
					}
				}
				if ($tokenIsActive) $activeTokens[] = $token;

			} else {
				$activeTokens[] = $token;
			}
		}

		return $activeTokens;
	}

	/**
	 * Updates the token credentials for all tokens in the given array.
	 *
	 * @param array $tokens Array of authentication tokens the credentials should be updated for
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function updateTokens(array $tokens) {
		foreach ($tokens as $token) {
			$token->updateCredentials();
		}
	}
}

?>