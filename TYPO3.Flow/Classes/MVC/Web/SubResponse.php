<?php
namespace TYPO3\FLOW3\MVC\Web;

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
 * A web specific sub response implementation
 *
 * @api
 * @scope prototype
 */
class SubResponse extends \TYPO3\FLOW3\MVC\Web\Response {

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\Response
	 */
	protected $parentResponse;

	/**
	 * @param \TYPO3\FLOW3\MVC\Web\Response $parentResponse
	 */
	public function __construct(\TYPO3\FLOW3\MVC\Web\Response $parentResponse) {
		$this->parentResponse = $parentResponse;
	}

	/**
	 * @return \TYPO3\FLOW3\MVC\Web\Response
	 */
	public function getParentResponse() {
		return $this->parentResponse;
	}

	/**
	 * Sets the HTTP status code and (optionally) a customized message in the parent response
	 *
	 * @param integer $code The status code
	 * @param string $message If specified, this message is sent instead of the standard message
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 * @see \TYPO3\FLOW3\MVC\Web\Response::setStatus()
	 */
	public function setStatus($code, $message = NULL) {
		$this->parentResponse->setStatus($code, $message);
	}

	/**
	 * Returns status code and status message from the parent response
	 *
	 * @return string The status code and status message, eg. "404 Not Found"
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 * @see \TYPO3\FLOW3\MVC\Web\Response::getStatus()
	 */
	public function getStatus() {
		return $this->parentResponse->getStatus();
	}

	/**
	 * Sets the specified HTTP header in the parent response
	 *
	 * @param string $name Name of the header, for example "Location", "Content-Description" etc.
	 * @param mixed $value The value of the given header
	 * @param boolean $replaceExistingHeader If a header with the same name should be replaced. Default is TRUE.
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 * @see \TYPO3\FLOW3\MVC\Web\Response::setHeader()
	 */
	public function setHeader($name, $value, $replaceExistingHeader = TRUE) {
		$this->parentResponse->setHeader($name, $value, $replaceExistingHeader);
	}

	/**
	 * Returns the HTTP headers - including the status header - of the parent response
	 *
	 * @return string The HTTP headers
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 * @see \TYPO3\FLOW3\MVC\Web\Response::getHeaders()
	 */
	public function getHeaders() {
		return $this->parentResponse->getHeaders();
	}
}
?>