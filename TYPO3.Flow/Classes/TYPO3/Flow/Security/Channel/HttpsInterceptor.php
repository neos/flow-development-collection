<?php
namespace TYPO3\Flow\Security\Channel;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * This security interceptor switches the current channel between HTTP and HTTPS protocol.
 *
 * @Flow\Scope("singleton")
 */
class HttpsInterceptor implements \TYPO3\Flow\Security\Authorization\InterceptorInterface {

	/**
	 * @var boolean
	 * @todo this has to be set by configuration
	 */
	protected $useSSL = FALSE;

	/**
	 * Constructor.
	 *
	 * @param \TYPO3\Flow\Security\Context $securityContext The current security context
	 * @param \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface $authenticationManager The authentication Manager
	 * @param \TYPO3\Flow\Log\SystemLoggerInterface $logger A logger to log security relevant actions
	 */
	public function __construct(
		\TYPO3\Flow\Security\Context $securityContext,
		\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface $authenticationManager,
		\TYPO3\Flow\Log\SystemLoggerInterface $logger
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