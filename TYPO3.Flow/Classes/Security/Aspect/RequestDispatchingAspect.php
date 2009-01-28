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
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 */

/**
 * The central security aspect, that invokes the security interceptors.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @aspect
 */
class RequestDispatchingAspect {

	/**
	 * @var \F3\FLOW3\Security\ContextHolderInterface A reference to the security contextholder
	 */
	protected $securityContextHolder;

	/**
	 * @var \F3\FLOW3\Security\Auhtorization\FirewallInterface A reference to the firewall
	 */
	protected $firewall;

	/**
	 * Constructor
	 *
	 * @param \F3\FLOW3\Security\ContextHolderInterface $securityContextHolder
	 * @param \F3\FLOW3\Security\Authorization\FirewallInterface $firewall
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Security\ContextHolderInterface $securityContextHolder, \F3\FLOW3\Security\Authorization\FirewallInterface $firewall) {
		$this->securityContextHolder = $securityContextHolder;
		$this->firewall = $firewall;
	}

	/**
	 * Advices the dispatch method so that illegal requests are blocked before invoking
	 * any controller.
	 *
	 * @around method(F3\FLOW3\MVC\Dispatcher->dispatch()) && setting(FLOW3: security: enable)
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed Result of the advice chain
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function blockIllegalRequests(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$request = $joinPoint->getMethodArgument('request');
		$this->securityContextHolder->initializeContext($request);
		$this->firewall->blockIllegalRequests($request);
		return $joinPoint->getAdviceChain()->proceed($joinPoint);
	}

	/**
	 * Catches Access Denied exceptions and instructs the response to redirect to a
	 * login page.
	 *
	 * @afterthrowing method(F3\FLOW3\MVC\Dispatcher->dispatch()) && setting(FLOW3: security: enable)
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function forwardAccessDeniedExceptionsToLoginPage(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$exception = $joinPoint->getException();
		$request = $joinPoint->getMethodArgument('request');
		if (!$request instanceof \F3\FLOW3\MVC\Web\Request || !$exception instanceof \F3\FLOW3\Security\Exception\AuthenticationRequired) throw $exception;

		$response = $joinPoint->getMethodArgument('response');

		$request->setDispatched(TRUE);
		$uri = 'login';
		$escapedUri = htmlentities($uri, ENT_QUOTES, 'utf-8');
		$response->setContent('<html><head><meta http-equiv="refresh" content="0;url=' . $escapedUri . '"/></head></html>');
		$response->setStatus(303);
		$response->setHeader('Location', (string)$uri);
	}
}
?>