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
 * The security ContextHolder ist a container to hold all security related information.
 * Depending on the implementation (strategy) of the ContextHolder the context may be stored or not.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_FLOW3_Security_ContextHolderInterface {

	/**
	 * Sets the current security context. Depending on the strategy the context may for example be stored
	 * in a session.
	 *
	 * @param F3_FLOW3_Security_ContextInterface $securityContext The current security context
	 * @return void
	 */
	public function setContext(F3_FLOW3_Security_Context $securityContext);

	/**
	 * Returns the current security context.
	 *
	 * @return F3_FLOW3_Security_ContextInterface The current security context
	 */
	public function getContext();

	/**
	 * Initializes the security context for the given request. Depending on the strategy the context might be
	 * loaded from a session. The AuthenticationManager has to be instanciated here, to set the authentication
	 * tokens.
	 *
	 * @param F3_FLOW3_MVC_Request $request The request the context should be initialized for
	 * @return void
	 */
	public function initializeContext(F3_FLOW3_MVC_Request $request);

	/**
	 * Clears the current security context.
	 *
	 * @return void
	 */
	public function clearContext();
}

?>