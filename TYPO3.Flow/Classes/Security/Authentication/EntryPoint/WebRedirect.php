<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication\EntryPoint;

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
 * An authentication entry point, that redirects to another webpage.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class WebRedirect implements \F3\FLOW3\Security\Authentication\EntryPointInterface {

	/**
	 * The configurations options
	 * @var array
	 */
	protected $options = array();

	/**
	 * Returns TRUE if the given request can be authenticated by the authentication provider
	 * represented by this entry point
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The current request
	 * @return boolean TRUE if authentication is possible
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canForward(\F3\FLOW3\MVC\RequestInterface $request) {
		return ($request instanceof \F3\FLOW3\MVC\Web\Request);
	}

	/**
	 * Sets the options array
	 *
	 * @param array $options An array of configuration options
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setOptions(array $options) {
		$this->options = $options;
	}

	/**
	 * Starts the authentication: Redirect to login page
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The current request
	 * @param \F3\FLOW3\MVC\ResponseInterface $response The current response
	 * @return void
	 */
	public function startAuthentication(\F3\FLOW3\MVC\RequestInterface $request, \F3\FLOW3\MVC\ResponseInterface $response) {
		if (!$this->canForward($request)) throw new \F3\FLOW3\Security\Exception\RequestTypeNotSupported('Unsupported request type for authentication entry point given.', 1237282462);
		if (!is_array($this->options) || !isset($this->options['uri'])) throw new \F3\FLOW3\Security\Exception\MissingConfiguration('The configuration for the WebRedirect authentication entry point is incorrect or missing.', 1237282583);

		$escapedUri = htmlentities($this->options['uri'], ENT_QUOTES, 'utf-8');
		$response->setContent('<html><head><meta http-equiv="refresh" content="0;url=' . $escapedUri . '"/></head></html>');
		$response->setStatus(303);
		$response->setHeader('Location', (string)$this->options['uri']);

	}
}

?>