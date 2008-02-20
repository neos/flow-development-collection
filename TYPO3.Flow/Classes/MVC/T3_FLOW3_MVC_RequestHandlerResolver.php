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
 * Analyzes the raw request and delivers a request handler which can handle it.
 * 
 * @package		FLOW3
 * @subpackage	MVC
 * @version 	$Id:T3_FLOW3_MVC_RequestHandlerResolver.php 467 2008-02-06 19:34:56Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_MVC_RequestHandlerResolver {

	/**
	 * @var T3_FLOW3_ComponentManagerInterface Reference to the component manager
	 */
	protected $componentManager;
	
	/**
	 * Constructs the Request Handler Resolver
	 *
	 * @param  T3_FLOW3_ComponentManagerInterface $componentManager: A reference to the component manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(T3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
	}
	
	/**
	 * Analyzes the raw request and tries to find a request handler which can handle
	 * it. If none is found, an exception is thrown.
	 *
	 * @return T3_FLOW3_MVC_RequestHandler A request handler
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveRequestHandler() {
		$availableRequestHandlerClassNames = $this->componentManager->getAllImplementationClassNamesForInterface('T3_FLOW3_MVC_RequestHandlerInterface');
		$suitableRequestHandlers = array();
		foreach ($availableRequestHandlerClassNames as $requestHandlerClassName) {
			$requestHandler = $this->componentManager->getComponent($requestHandlerClassName);
			if ($requestHandler->canHandleRequest()) {
				$priority = $requestHandler->getPriority();
				if (isset($suitableRequestHandlers[$priority])) throw new LogicException('More than one request handler with the same priority can handle the request, but only one handler may be active at a time!', 1176475350);
				$suitableRequestHandlers[$priority] = $requestHandler;
			}
		}
		ksort($suitableRequestHandlers);
		return array_pop($suitableRequestHandlers);
	}
}

?>