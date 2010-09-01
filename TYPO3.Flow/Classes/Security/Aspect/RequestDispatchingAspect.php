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
 * The central security aspect, that invokes the security interceptors.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @aspect
 */
class RequestDispatchingAspect {

	/**
	 * @var F3\FLOW3\Security\Context A reference to the security context
	 */
	protected $securityContext;

	/**
	 * @var F3\FLOW3\Security\Auhtorization\FirewallInterface A reference to the firewall
	 */
	protected $firewall;

	/**
	 * @var F3\FLOW3\Security\Channel\RequestHashService The request hash service
	 */
	protected $requestHashService;

	/**
	 * Constructor
	 *
	 * @param F3\FLOW3\Security\Context $securityContext
	 * @param F3\FLOW3\Security\Authorization\FirewallInterface $firewall
	 * @param F3\FLOW3\Security\Channel\RequestHashService $requestHashService
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Security\Context $securityContext, \F3\FLOW3\Security\Authorization\FirewallInterface $firewall, \F3\FLOW3\Security\Channel\RequestHashService $requestHashService) {
		$this->securityContext = $securityContext;
		$this->firewall = $firewall;
		$this->requestHashService = $requestHashService;
	}

	/**
	 * Advices the dispatch method to initialize the security framework.
	 *
	 * @around method(F3\FLOW3\MVC\Dispatcher->dispatch()) && setting(FLOW3.security.enable)
	 * @param F3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed Result of the advice chain
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function initializeSecurity(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$request = $joinPoint->getMethodArgument('request');
		$this->securityContext->initialize($request);
		return $joinPoint->getAdviceChain()->proceed($joinPoint);
	}

	/**
	 * Advices the dispatch method so that illegal requests are blocked before invoking
	 * any controller.
	 *
	 * @around method(F3\FLOW3\MVC\Dispatcher->dispatch()) && setting(FLOW3.security.enable)
	 * @param F3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed Result of the advice chain
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function blockIllegalRequestsAndForwardToAuthenticationEntryPoints(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$request = $joinPoint->getMethodArgument('request');
		$this->firewall->blockIllegalRequests($request);

		try {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		} catch (\F3\FLOW3\Security\Exception\AuthenticationRequiredException $exception) {
			$response = $joinPoint->getMethodArgument('response');

			$entryPointFound = FALSE;
			foreach ($this->securityContext->getAuthenticationTokens() as $token) {
				$entryPoint = $token->getAuthenticationEntryPoint();

				if ($entryPoint !== NULL && $entryPoint->canForward($request)) {
					$entryPointFound = TRUE;
					$entryPoint->startAuthentication($request, $response);
				}
			}
			if ($entryPointFound === FALSE) throw $exception;
		}
	}
}
?>