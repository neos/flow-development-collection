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
 * A Manager for the Request Processor Chain. This chain is used to post-process
 * the Request object prior to handing it over to the Request Dispatcher. 
 * 
 * @package		FLOW3
 * @subpackage	MVC
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_MVC_RequestProcessorChainManager {
	
	/**
	 * @var array Supported request types 
	 */
	protected $supportedRequestTypes = array('T3_FLOW3_MVC_Request', 'T3_FLOW3_MVC_Web_Request', 'T3_FLOW3_MVC_CLI_Request');
	
	/**
	 * @var array Registered request processors, grouped by request type
	 */
	protected $requestProcessors = array();
	
	/**
	 * Processes the given request object by invoking the processors
	 * of the processor chain.
	 *
	 * @param  T3_FLOW3_MVC_Request $request:		The request object - changes are applied directly to this object by the processors.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequest(T3_FLOW3_MVC_Request $request) {
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
	 * @param  T3_FLOW3_MVC_RequestProcessorInterface	$requestProcessor: The request processor
	 * @param  string										$requestType: Type (class- or interface name) of the request this processor is interested in 
	 * @return void
	 * @throws T3_FLOW3_MVC_Exception_InvalidRequestType if the request type is not supported. 
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerRequestProcessor(T3_FLOW3_MVC_RequestProcessorInterface $requestProcessor, $requestType) {
		if (!in_array($requestType, $this->supportedRequestTypes, TRUE)) throw new T3_FLOW3_MVC_Exception_InvalidRequestType('"' . $requestType . '" is not a valid request type - or at least it\'s not supported by the Request Processor Chain.', 1187260972);
		$this->requestProcessors[$requestType][] = $requestProcessor;
	}
	
	/**
	 * Unregisters the given Request Processor. If a request type is specified,
	 * the Processor will only be removed from that chain accordingly.
	 * 
	 * Triggers _no_ error if the request processor did not exist.
	 * 
	 * @param  T3_FLOW3_MVC_RequestProcessorInterface	$requestProcessor: The request processor
	 * @param  string										$requestType: Type (class- or interface name) of the request this processor is interested in 
	 * @return void
	 * @throws T3_FLOW3_MVC_Exception_InvalidRequestType if the request type is not supported. 
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function unregisterRequestProcessor(T3_FLOW3_MVC_RequestProcessorInterface $requestProcessor, $requestType = NULL) {
		if ($requestType !== NULL) {
			if (!in_array($requestType, $this->supportedRequestTypes, TRUE)) throw new T3_FLOW3_MVC_Exception_InvalidRequestType('"' . $requestType . '" is not a valid request type - or at least it\'s not supported by the Request Processor Chain.', 1187261072);
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
	 * @return array							An array of request types of request processor objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRegisteredRequestProcessors() {
		return $this->requestProcessors;
	}
}

?>