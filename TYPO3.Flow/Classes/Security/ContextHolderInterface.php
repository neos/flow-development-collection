<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security;

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
 * The security ContextHolder ist a container to hold all security related information.
 * Depending on the implementation (strategy) of the ContextHolder the context may be stored or not.
 *
 * @version $Id$
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface ContextHolderInterface {

	/**
	 * Sets the current security context. Depending on the strategy the context may for example be stored
	 * in a session.
	 *
	 * @param \F3\FLOW3\Security\ContextInterface $securityContext The current security context
	 * @return void
	 */
	public function setContext(\F3\FLOW3\Security\Context $securityContext);

	/**
	 * Returns the current security context.
	 *
	 * @return \F3\FLOW3\Security\ContextInterface The current security context
	 * @throws \F3\FLOW3\Security\Exception\NoContextAvailable if no context is available
	 */
	public function getContext();

	/**
	 * Initializes the security context for the given request. Depending on the strategy the context might be
	 * loaded from a session. The AuthenticationManager has to be instanciated here, to set the authentication
	 * tokens.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request the context should be initialized for
	 * @return void
	 */
	public function initializeContext(\F3\FLOW3\MVC\RequestInterface $request);

	/**
	 * Clears the current security context.
	 *
	 * @return void
	 */
	public function clearContext();
}

?>