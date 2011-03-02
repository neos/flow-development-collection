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
 * @scope singleton
 * @aspect
 */
class LoggingAspect {

	/**
	 * @var \F3\FLOW3\Log\SecurityLoggerInterface
	 */
	protected $securityLogger;

	/**
	 * @var boolean
	 */
	protected $alreadyLoggedAuthenticateCall = FALSE;

	/**
	 * @param \F3\FLOW3\Log\SecurityLoggerInterface $securityLogger
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSecurityLogger(\F3\FLOW3\Log\SecurityLoggerInterface $securityLogger) {
		$this->securityLogger = $securityLogger;
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
			$this->securityLogger->log('Authentication failed: "' . $exception->getMessage() . '" #' . $exception->getCode(), LOG_NOTICE);
			throw $exception;
		} elseif ($this->alreadyLoggedAuthenticateCall === FALSE) {
			if ($joinPoint->getProxy()->getSecurityContext()->getAccount() !== NULL) {
				$this->securityLogger->log('Successfully re-authenticated tokens for account "' . $joinPoint->getProxy()->getSecurityContext()->getAccount()->getAccountIdentifier() . '"', LOG_INFO);
			} else {
				$this->securityLogger->log('No account authenticated', LOG_INFO);
			}
			$this->alreadyLoggedAuthenticateCall = TRUE;
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
		$this->securityLogger->log('Logged out ' . count($accountIdentifiers) . ' account(s). (' . implode(', ', $accountIdentifiers) . ')', LOG_INFO);
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

		switch ($token->getAuthenticationStatus()) {
			case \F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL :
				$this->securityLogger->log('Successfully authenticated token: ' . $token, LOG_NOTICE, array(), 'FLOW3', $joinPoint->getClassName(), $joinPoint->getMethodName());
				$this->alreadyLoggedAuthenticateCall = TRUE;
			break;
			case \F3\FLOW3\Security\Authentication\TokenInterface::WRONG_CREDENTIALS :
				$this->securityLogger->log('Wrong credentials given for token: ' . $token, LOG_WARNING, array(), 'FLOW3', $joinPoint->getClassName(), $joinPoint->getMethodName());
			break;
			case \F3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN :
				$this->securityLogger->log('No credentials given or no account found for token: ' . $token, LOG_WARNING, array(), 'FLOW3', $joinPoint->getClassName(), $joinPoint->getMethodName());
			break;
		}
	}

	/**
	 * Logs calls and results of decideOnJoinPoint()
	 *
	 * @afterthrowing method(F3\FLOW3\Security\Authorization\AccessDecisionVoterManager->decideOnJoinPoint())
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function logJoinPointAccessDecisions(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$exception = $joinPoint->getException();

		$subjectJoinPoint = $joinPoint->getMethodArgument('joinPoint');
		$message = $exception->getMessage() . ' to method ' . $subjectJoinPoint->getClassName() . '::' . $subjectJoinPoint->getMethodName() . '().';

		$this->securityLogger->log($message, \LOG_INFO);

		throw $exception;
	}
}

?>