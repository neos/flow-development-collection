<?php
declare(ENCODING = 'utf-8');
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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id: AbstractController.php 2203 2009-05-12 18:44:47Z networkteam_hlubek $
 */

/**
 * The controller context contains information from the controller
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id: AbstractController.php 2203 2009-05-12 18:44:47Z networkteam_hlubek $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ControllerContext {

	/**
	 * @var \F3\FLOW3\MVC\RequestInterface
	 */
	protected $request;

	/**
	 * @var \F3\FLOW3\MVC\ResponseInterface
	 */
	protected $response;

	/**
	 * @var \F3\FLOW3\MVC\Controller\Arguments
	 */
	protected $arguments;

	/**
	 * @var \F3\FLOW3\Property\MappingResults
	 */
	protected $argumentsMappingResults;

	/**
	 * Get the request of the controller
	 *
	 * @return \F3\FLOW3\MVC\RequestInterface
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Set the request of the controller
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request
	 * @return void
	 * @internal
	 */
	public function setRequest(\F3\FLOW3\MVC\RequestInterface $request) {
		$this->request = $request;
	}

	/**
	 * Get the response of the controller
	 *
	 * @return \F3\FLOW3\MVC\RequestInterface
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Set the response of the controller
	 *
	 * @param \F3\FLOW3\MVC\ResponseInterface $request
	 * @return void
	 * @internal
	 */
	public function setResponse(\F3\FLOW3\MVC\ResponseInterface $response) {
		$this->response = $response;
	}

	/**
	 * Get the arguments of the controller
	 *
	 * @return \F3\FLOW3\MVC\Controller\Arguments
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Set the arguments of the controller
	 *
	 * @param \F3\FLOW3\MVC\Controller\Arguments $arguments
	 * @return void
	 * @internal
	 */
	public function setArguments(\F3\FLOW3\MVC\Controller\Arguments $arguments) {
		$this->arguments = $arguments;
	}

	/**
	 * Get the arguments mapping results of the controller
	 *
	 * @return \F3\FLOW3\Property\MappingResults
	 */
	public function getArgumentsMappingResults() {
		return $this->argumentsMappingResults;
	}

	/**
	 * Set the arguments mapping results of the controller
	 *
	 * @param \F3\FLOW3\Property\MappingResults $argumentsMappingResults
	 * @return void
	 * @internal
	 */
	public function setArgumentsMappingResults(\F3\FLOW3\Property\MappingResults $argumentsMappingResults) {
		$this->argumentsMappingResults = $argumentsMappingResults;
	}

}
?>