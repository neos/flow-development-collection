<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 */

/**
 * This is the default session implementation of security ContextHolder.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_ContextHolderSession implements F3_FLOW3_Security_ContextHolderInterface {#

	/**
	 * @var F3_FLOW3_Component_FactoryInterface
	 */
	protected $componentFactory = NULL;

	/**
	 * @var F3_FLOW3_Security_Authentication_ManagerInterface
	 */
	protected $authenticationManager = NULL;

	/**
	 * @var F3_FLOW3_Session_Interface The user session
	 */
	protected $session = NULL;

	/**
	 * Contstructor.
	 *
	 * @param F3_FLOW3_Session_Interface $session An implementaion of a session
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3_FLOW3_Session_Interface $session) {
		$this->session = $session;
		$this->session->start();
	}

	/**
	 * Stores the current security context to the session.
	 *
	 * @param F3_FLOW3_Security_Context $securityContext The current security context
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setContext(F3_FLOW3_Security_Context $securityContext) {
		$this->session->storeContents($securityContext, 'F3_FLOW3_Security_ContextHolderSession');
	}

	/**
	 * Returns the current F3_FLOW3_Security_Context. A new one is created if there was none in the session.
	 *
	 * @return F3_FLOW3_Security_Context The current security context
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getContext() {
		$context = $this->session->getContentsByKey('F3_FLOW3_Security_ContextHolderSession');

		if($context instanceof F3_FLOW3_Security_Context) return $context;
		return $this->componentFactory->getComponent('F3_FLOW3_Security_Context');
	}

	/**
	 * Initializes the security context for the given request. It is loaded from the session.
	 *
	 * @param F3_FLOW3_MVC_Request $request The request the context should be initialized for
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function initializeContext(F3_FLOW3_MVC_Request $request) {
		$context = $this->getContext();
		$context->setRequest($request);

		$managerTokens = $this->authenticationManager->getTokens();
		$sessionTokens = $context->getAuthenticationTokens();
		$mergedTokens = $this->mergeTokens($managerTokens, $sessionTokens);

		$this->updateTokenCredentials($mergedTokens);
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
	 * Inject the component manager
	 *
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory The component factory
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectComponentFactory(F3_FLOW3_Component_FactoryInterface $componentFactory) {
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Inject the authentication manager
	 *
	 * @param F3_FLOW3_Security_Authentication_ManagerInterface $componentManager The authentication manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectAuthenticationManager(F3_FLOW3_Security_Authentication_ManagerInterface $authenticationManager) {
		$this->authenticationManager = $authenticationManager;
	}

	/**
	 * Merges the session and manager tokens. All manager tokens types will be in the result array
	 * If a specific type is found in the session this token replaces the one I(of the same type)
	 * given by the manager.
	 *
	 * @params array Array of tokens provided by the authentication manager
	 * @params array Array of tokens resotored from the session
	 * @return array Array of F3_FLOW3_Security_Authentication_TokenInterface objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	private function mergeTokens($managerTokens, $sessionTokens) {
		$resultTokens = array();

		if(!is_array($managerTokens)) return $resultTokens;

		foreach($managerTokens as $managerToken) {
			$noCorrespondingSessionTokenFound = TRUE;

			if(!is_array($sessionTokens)) continue;

			foreach($sessionTokens as $sessionToken) {
				$managerTokenClass = get_class($managerToken);

				if($sessionToken instanceof $managerTokenClass) {
					$resultTokens[] = $sessionToken;
					$noCorrespondingSessionTokenFound = FALSE;
				}
			}

			if($noCorrespondingSessionTokenFound) $resultTokens[] = $managerToken;
		}

		return $resultTokens;
	}

	/**
	 * Updates the token credentials for all tokens in the given array
	 *
	 * @param array Array of authentication tokens the credentials should be updated for
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	private function updateTokenCredentials(array $tokens) {
		foreach($tokens as $token) {
			$token->updateCredentials();
		}
	}

	/**
	 * Destructor, closes the session.
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo Implement proper handling of uninitialized sessions
	 */
	public function __destruct() {
		try {
			$this->session->close();
		} catch (Exception $exception) {
		}
	}
}

?>