<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication;

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
 * Contract for an authentication entry point
 *
 * @version $Id$
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface EntryPointInterface {

	/**
	 * Returns TRUE if the given request can be authenticated by the authentication provider
	 * represented by this entry point
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The current request
	 * @return boolean TRUE if authentication is possible
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canForward(\F3\FLOW3\MVC\RequestInterface $request);

	/**
	 * Sets the options array
	 *
	 * @param array $options An array of configuration options
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setOptions(array $options);

	/**
	 * Starts the authentication. (e.g. redirect to login page or send 401 HTTP header)
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The current request
	 * @param \F3\FLOW3\MVC\ResponseInterface $response The current response
	 * @return void
	 */
	public function startAuthentication(\F3\FLOW3\MVC\RequestInterface $request, \F3\FLOW3\MVC\ResponseInterface $response);
}

?>