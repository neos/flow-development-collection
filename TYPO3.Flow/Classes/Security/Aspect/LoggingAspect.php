<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Aspect;

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
 * An aspect which centralizes the logging of security relevant actions.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @aspect
 */
class LoggingAspect {

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * Constructor.
	 *
	 * @param \F3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Logs calls and results of the authenticate() method of the Authentication Manager
	 *
	 * @after within(F3\FLOW3\Security\Authentication\AuthenticationManagerInterface) && method(.*->authenticate())
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logManagerAuthenticate(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		if ($joinPoint->hasException()) {
			$exception = $joinPoint->getException();
			$this->systemLogger->log('Authentication failed: "' . $exception->getMessage() . '" #' . $exception->getCode(), LOG_WARNING);
			throw $exception;
		} else {
			$this->systemLogger->log('Authentication successful.', LOG_INFO);
		}
	}

	/**
	 * Logs calls and results of the logout() method of the Authentication Manager
	 *
	 * @afterreturning within(F3\FLOW3\Security\Authentication\AuthenticationManagerInterface) && method(.*->logout())
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logManagerLogout(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$accountIdentifiers = array();
		foreach ($joinPoint->getProxy()->getSecurityContext()->getAuthenticationTokens() as $token) {
			$account = $token->getAccount();
			if ($account !== NULL) {
				$accountIdentifiers[] = $account->getAccountIdentifier();
			}
		}
		$this->systemLogger->log('Logged out ' . count($accountIdentifiers) . ' account(s). (' . implode(', ', $accountIdentifiers) . ')', LOG_INFO);
	}

	/**
	 * Logs calls and results of the authenticate() method of an authentication provider
	 *
	 * @afterreturning within(F3\FLOW3\Security\Authentication\AuthenticationProviderInterface) && method(.*->authenticate())
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logPersistedUsernamePasswordProviderAuthenticate(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$token = $joinPoint->getMethodArgument('authenticationToken');
		$credentials = $token->getCredentials();

		switch ($token->getAuthenticationStatus()) {
			case \F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL :
				$this->systemLogger->log('Successfully authenticated user "' . $credentials['username'] . '".', LOG_INFO, array(), 'FLOW3', 'F3\FLOW3\Security\Authentication\Provider\PersistedUsernamePasswordProvider', 'authenticate');
			break;
			case \F3\FLOW3\Security\Authentication\TokenInterface::WRONG_CREDENTIALS :
				$this->systemLogger->log('Wrong password given for user "' . $credentials['username'] . '".', LOG_WARNING, array(), 'FLOW3', 'F3\FLOW3\Security\Authentication\Provider\PersistedUsernamePasswordProvider', 'authenticate');
			break;
			case \F3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN :
				$this->systemLogger->log('No credentials given or no account found with username "' . $credentials['username'] . '".', LOG_WARNING, array(), 'FLOW3', 'F3\FLOW3\Security\Authentication\Provider\PersistedUsernamePasswordProvider', 'authenticate');
			break;
		}
	}
}

?>