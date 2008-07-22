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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_RequestHandlerResolver.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Analyzes the raw request and delivers a request handler which can handle it.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_RequestHandlerResolver.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_RequestHandlerResolver {

	/**
	 * @var F3_FLOW3_ComponentFactoryInterface Reference to the component factory
	 */
	protected $componentFactory;

	/**
	 * @var F3_FLOW3_Configuration_Container FLOW3 configuration
	 */
	protected $configuration;

	/**
	 * Constructs the Request Handler Resolver
	 *
	 * @param F3_FLOW3_Configuration_Container $configuration The FLOW3 configuration
	 * @param F3_FLOW3_ComponentFactoryInterface $componentFactory A reference to the component factory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Configuration_Container $configuration, F3_FLOW3_Component_FactoryInterface $componentFactory) {
		$this->configuration = $configuration;
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Analyzes the raw request and tries to find a request handler which can handle
	 * it. If none is found, an exception is thrown.
	 *
	 * @return F3_FLOW3_MVC_RequestHandler A request handler
	 * @throws F3_FLOW3_MVC_Exception
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveRequestHandler() {
		$availableRequestHandlerClassNames = $this->configuration->mvc->availableRequestHandlers;

		$suitableRequestHandlers = array();
		foreach ($availableRequestHandlerClassNames as $requestHandlerClassName) {
			$requestHandler = $this->componentFactory->getComponent($requestHandlerClassName);
			if ($requestHandler->canHandleRequest()) {
				$priority = $requestHandler->getPriority();
				if (isset($suitableRequestHandlers[$priority])) throw new LogicException('More than one request handler with the same priority can handle the request, but only one handler may be active at a time!', 1176475350);
				$suitableRequestHandlers[$priority] = $requestHandler;
			}
		}
		if (count($suitableRequestHandlers) == 0) throw new F3_FLOW3_MVC_Exception('No suitable request handler found.', 1205414233);
		ksort($suitableRequestHandlers);
		return array_pop($suitableRequestHandlers);
	}
}

?>