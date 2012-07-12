<?php
namespace TYPO3\FLOW3\Security\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\FLOW3\Mvc\ActionRequest;

/**
 * The central security aspect, that invokes the security interceptors.
 *
 * @FLOW3\Scope("singleton")
 * @FLOW3\Aspect
 */
class RequestDispatchingAspect {

	/**
	 * @var \TYPO3\FLOW3\Log\SecurityLoggerInterface
	 */
	protected $securityLogger;

	/**
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @var \TYPO3\FLOW3\Security\Authorization\FirewallInterface
	 */
	protected $firewall;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\FLOW3\Security\Context $securityContext
	 * @param \TYPO3\FLOW3\Security\Authorization\FirewallInterface $firewall
	 * @param \TYPO3\FLOW3\Log\SecurityLoggerInterface $securityLogger
	 */
	public function __construct(\TYPO3\FLOW3\Security\Context $securityContext, \TYPO3\FLOW3\Security\Authorization\FirewallInterface $firewall, \TYPO3\FLOW3\Log\SecurityLoggerInterface $securityLogger) {
		$this->securityContext = $securityContext;
		$this->firewall = $firewall;
		$this->securityLogger = $securityLogger;
	}

	/**
	 * Advices the dispatch method so that illegal action requests are blocked before
	 * invoking any controller.
	 *
	 * The "request" referred to within this method is an ActionRequest or some other
	 * dispatchable request implementing RequestInterface. Note that we don't deal
	 * with HTTP requests here.
	 *
	 * @FLOW3\Around("setting(TYPO3.FLOW3.security.enable) && method(TYPO3\FLOW3\Mvc\Dispatcher->dispatch())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed Result of the advice chain
	 * @throws \TYPO3\FLOW3\Security\Exception\AccessDeniedException
	 * @throws \TYPO3\FLOW3\Security\Exception\AuthenticationRequiredException
	 */
	public function blockIllegalRequestsAndForwardToAuthenticationEntryPoints(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$request = $joinPoint->getMethodArgument('request');
		if (!$request instanceof ActionRequest) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		}

		try {
			$this->firewall->blockIllegalRequests($request);
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		} catch (\TYPO3\FLOW3\Security\Exception\AuthenticationRequiredException $exception) {
			$response = $joinPoint->getMethodArgument('response');

			$entryPointFound = FALSE;
			foreach ($this->securityContext->getAuthenticationTokens() as $token) {
				$entryPoint = $token->getAuthenticationEntryPoint();

				if ($entryPoint !== NULL) {
					$entryPointFound = TRUE;
					if ($entryPoint instanceof \TYPO3\FLOW3\Security\Authentication\EntryPoint\WebRedirect) {
						$options = $entryPoint->getOptions();
						$this->securityLogger->log('Redirecting to authentication entry point with URI ' . (isset($options['uri']) ? $options['uri'] : '- undefined -'), LOG_INFO);
					} else {
						$this->securityLogger->log('Starting authentication with entry point of type ' . get_class($entryPoint), LOG_INFO);
					}
					$this->securityContext->setInterceptedRequest($request->getMainRequest());
					$entryPoint->startAuthentication($request->getHttpRequest(), $response);
				}
			}
			if ($entryPointFound === FALSE) {
				$this->securityLogger->log('No authentication entry point found for active tokens, therefore cannot authenticate or redirect to authentication automatically.', LOG_NOTICE);
				throw $exception;
			}
		} catch (\TYPO3\FLOW3\Security\Exception\AccessDeniedException $exception) {
			$this->securityLogger->log('Access denied', LOG_WARNING);
			throw $exception;
		}
	}
}
?>
