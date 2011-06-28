<?php
namespace TYPO3\FLOW3\Security\Channel;

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
 * This security interceptor switches the current channel between HTTP and HTTPS protocol.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
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
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invoke() {

	}
}

?>