<?php
namespace TYPO3\FLOW3\Mvc\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * The controller context holds information about the request, response, arguments
 * and further details of a controller. Instances of this class act as a container
 * for conveniently passing the information to other classes who need it, usually
 * views being views or view helpers.
 *
 * @api
 */
class ControllerContext {

	/**
	 * @var \TYPO3\FLOW3\Mvc\RequestInterface
	 */
	protected $request;

	/**
	 * @var \TYPO3\FLOW3\Http\Response
	 */
	protected $response;

	/**
	 * @var \TYPO3\FLOW3\Mvc\Controller\Arguments
	 */
	protected $arguments;

	/**
	 * @var \TYPO3\FLOW3\Mvc\Routing\UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * @var \TYPO3\FLOW3\Mvc\FlashMessageContainer
	 */
	protected $flashMessageContainer;

	/**
	 * Constructs this context
	 *
	 * @param \TYPO3\FLOW3\MVC\RequestInterface $request
	 * @param \TYPO3\FLOW3\MVC\ResponseInterface $response
	 * @param \TYPO3\FLOW3\MVC\Controller\Arguments $arguments
	 * @param \TYPO3\FLOW3\MVC\Web\Routing\UriBuilder $uriBuilder
	 * @param \TYPO3\FLOW3\MVC\FlashMessageContainer $flashMessageContainer The flash messages
	 */
	public function __construct(\TYPO3\FLOW3\MVC\RequestInterface $request, \TYPO3\FLOW3\MVC\ResponseInterface $response, \TYPO3\FLOW3\MVC\Controller\Arguments $arguments,
			\TYPO3\FLOW3\MVC\Web\Routing\UriBuilder $uriBuilder, \TYPO3\FLOW3\MVC\FlashMessageContainer $flashMessageContainer) {
		$this->request = $request;
		$this->response = $response;
		$this->arguments = $arguments;
		$this->uriBuilder = $uriBuilder;
		$this->flashMessageContainer = $flashMessageContainer;
	}

	/**
	 * Get the request of the controller
	 *
	 * @return \TYPO3\FLOW3\Mvc\RequestInterface
	 * @api
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Get the response of the controller
	 *
	 * @return \TYPO3\FLOW3\Mvc\RequestInterface
	 * @api
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Get the arguments of the controller
	 *
	 * @return \TYPO3\FLOW3\Mvc\Controller\Arguments
	 * @api
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Returns the URI Builder bound to this context
	 *
	 * @return \TYPO3\FLOW3\Mvc\Routing\UriBuilder
	 * @api
	 */
	public function getUriBuilder() {
		return $this->uriBuilder;
	}

	/**
	 * Get the flash message container
	 *
	 * @return \TYPO3\FLOW3\Mvc\FlashMessageContainer A container for flash messages
	 * @api
	 */
	public function getFlashMessageContainer() {
		return $this->flashMessageContainer;
	}
}
?>