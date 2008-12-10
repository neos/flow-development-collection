<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC;

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
 * @subpackage MVC
 * @version $Id$
 */

/**
 * A Manager for the Request Processor Chain. This chain is used to post-process
 * the Request object prior to handing it over to the Request Dispatcher.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class RequestProcessorChainManager {

	/**
	 * @var array Supported request types
	 */
	protected $supportedRequestTypes = array('F3\FLOW3\MVC\Request', 'F3\FLOW3\MVC\Web\Request', 'F3\FLOW3\MVC\CLI\Request');

	/**
	 * @var array Registered request processors, grouped by request type
	 */
	protected $requestProcessors = array();

	/**
	 * Processes the given request object by invoking the processors
	 * of the processor chain.
	 *
	 * @param \F3\FLOW3\MVC\Request $request The request object - changes are applied directly to this object by the processors.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequest(\F3\FLOW3\MVC\Request $request) {
		$requestTypes = array_keys($this->requestProcessors);
		foreach ($requestTypes as $requestType) {
			if ($request instanceof $requestType) {
				foreach ($this->requestProcessors[$requestType] as $requestProcessor) {
					$requestProcessor->processRequest($request);
				}
			}
		}
	}

	/**
	 * Registers a Request Processor for the specified request type.
	 *
	 * @param \F3\FLOW3\MVC\RequestProcessorInterface $requestProcessor: The request processor
	 * @param string $requestType: Type (class- or interface name) of the request this processor is interested in
	 * @return void
	 * @throws \F3\FLOW3\MVC\Exception\InvalidRequestType if the request type is not supported.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerRequestProcessor(\F3\FLOW3\MVC\RequestProcessorInterface $requestProcessor, $requestType) {
		if (!in_array($requestType, $this->supportedRequestTypes, TRUE)) throw new \F3\FLOW3\MVC\Exception\InvalidRequestType('"' . $requestType . '" is not a valid request type - or at least it\'s not supported by the Request Processor Chain.', 1187260972);
		$this->requestProcessors[$requestType][] = $requestProcessor;
	}

	/**
	 * Unregisters the given Request Processor. If a request type is specified,
	 * the Processor will only be removed from that chain accordingly.
	 *
	 * Triggers _no_ error if the request processor did not exist.
	 *
	 * @param \F3\FLOW3\MVC\RequestProcessorInterface $requestProcessor The request processor
	 * @param string $requestType Type (class- or interface name) of the request this processor is interested in
	 * @return void
	 * @throws \F3\FLOW3\MVC\Exception\InvalidRequestType if the request type is not supported.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function unregisterRequestProcessor(\F3\FLOW3\MVC\RequestProcessorInterface $requestProcessor, $requestType = NULL) {
		if ($requestType !== NULL) {
			if (!in_array($requestType, $this->supportedRequestTypes, TRUE)) throw new \F3\FLOW3\MVC\Exception\InvalidRequestType('"' . $requestType . '" is not a valid request type - or at least it\'s not supported by the Request Processor Chain.', 1187261072);
			foreach ($this->requestProcessors[$requestType] as $index => $existingRequestProcessor) {
				if ($existingRequestProcessor === $requestProcessor) {
					unset($this->requestProcessors[$requestType][$index]);
				}
			}
		} else {
			foreach ($this->requestProcessors as $requestType => $requestProcessorsForThisType) {
				foreach ($requestProcessorsForThisType as $index => $existingRequestProcessor) {
					if ($existingRequestProcessor === $requestProcessor) {
						unset($this->requestProcessors[$requestType][$index]);
					}
				}
			}
		}
	}

	/**
	 * Returns an array of all registered request processors, grouped by request type.
	 *
	 * @return array An array of request types of request processor objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRegisteredRequestProcessors() {
		return $this->requestProcessors;
	}
}

?>