<?php
namespace F3\FLOW3\MVC\Controller;

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
 * A generic Controller exception
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Exception extends \F3\FLOW3\Exception {

	/**
	 * @var \F3\FLOW3\MVC\RequestInterface
	 */
	protected $request;

	/**
	 * Overwrites parent constructor to be able to inject current request object.
	 *
	 * @param string $message
	 * @param integer $code
	 * @param \Exception $previousException
	 * @param \F3\FLOW3\MVC\RequestInterface $request
	 * @see \Exception
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function __construct($message = '', $code = 0, \Exception $previousException = NULL, \F3\FLOW3\MVC\RequestInterface $request) {
		$this->request = $request;
		parent::__construct($message, $code, $previousException);
	}

	/**
	 * Returns the request object that exception belongs to.
	 *
	 * @return \F3\FLOW3\MVC\RequestInterface
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function getRequest() {
		return $this->request;
	}

}

?>