<?php
namespace TYPO3\FLOW3\Security\Channel;

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

/**
 * This security interceptor switches the current channel between HTTP and HTTPS protocol.
 *
 * @FLOW3\Scope("singleton")
 */
class HttpsInterceptor implements \TYPO3\FLOW3\Security\Authorization\InterceptorInterface {

	/**
	 * @var boolean
	 * @todo this has to be set by configuration
	 */
	protected $useSSL = FALSE;

	/**
	 * Constructor.
	 *
	 * @param \TYPO3\FLOW3\Security\Context $securityContext The current security context
	 * @param \TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface $authenticationManager The authentication Manager
	 * @param \TYPO3\FLOW3\Log\SystemLoggerInterface $logger A logger to log security relevant actions
	 */
	public function __construct(
		\TYPO3\FLOW3\Security\Context $securityContext,
		\TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface $authenticationManager,
		\TYPO3\FLOW3\Log\SystemLoggerInterface $logger
	) {

	}

	/**
	 * Redirects the current request to HTTP or HTTPS depending on $this->useSSL;
	 *
	 * @return boolean TRUE if the security checks was passed
	 */
	public function invoke() {

	}
}

?>