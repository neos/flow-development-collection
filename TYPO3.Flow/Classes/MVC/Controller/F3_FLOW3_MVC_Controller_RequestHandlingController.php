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
 * @version $Id:F3_FLOW3_MVC_Controller_RequestHandlingController.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * An abstract base class for Controllers which can handle requests
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Controller_RequestHandlingController.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_Controller_RequestHandlingController extends F3_FLOW3_MVC_Controller_AbstractController {

	/**
	 * @var F3_FLOW3_MVC_Request The current request
	 */
	protected $request;

	/**
	 * @var F3_FLOW3_MVC_Response The response which will be returned by this action controller
	 */
	protected $response;

	/**
	 * @var F3_FLOW3_MVC_Controller_Arguments Arguments passed to the controller
	 */
	protected $arguments;

	/**
	 * @var F3_FLOW3_MVC_Property_Mapper A property mapper for mapping the arguments
	 */
	protected $propertyMapper;

	/**
	 * @var F3_FLOW3_Property_MappingResults Mapping results of the arguments mapping process
	 */
	protected $argumentMappingResults;

	/**
	 * @var array An array of supported request types. By default all kinds of request are supported. Modify or replace this array if your specific controller only supports certain request types.
	 */
	protected $supportedRequestTypes = array('F3_FLOW3_MVC_Request');

	/**
	 * Constructs the controller.
	 *
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory A reference to the Component Factory
	 * @param F3_FLOW3_Package_ManagerInterface $packageManager A reference to the Package Manager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_FactoryInterface $componentFactory, F3_FLOW3_Package_ManagerInterface $packageManager) {
		$this->arguments = $componentFactory->getComponent('F3_FLOW3_MVC_Controller_Arguments');
		parent::__construct($componentFactory, $packageManager);
	}

	/**
	 * Injects a property mapper
	 *
	 * @param F3_FLOW3_Property_Mapper $propertyMapper
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPropertyMapper(F3_FLOW3_Property_Mapper $propertyMapper) {
		$this->propertyMapper = $propertyMapper;
	}

	/**
	 * Checks if the current request type is supported by the controller.
	 *
	 * If your controller only supports certain request types, either
	 * replace / modify the supporteRequestTypes property or override this
	 * method.
	 *
	 * @param F3_FLOW3_MVC_Request $request The current request
	 * @return boolean TRUE if this request type is supported, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canProcessRequest($request) {
		foreach ($this->supportedRequestTypes as $supportedRequestType) {
			if ($request instanceof $supportedRequestType) return TRUE;
		}
		return FALSE;
	}

	/**
	 * Processes a general request. The result can be returned by altering the given response.
	 *
	 * @param F3_FLOW3_MVC_Request $request The request object
	 * @param F3_FLOW3_MVC_Response $response The response, modified by this handler
	 * @return void
	 * @throws F3_FLOW3_MVC_Exception_UnsupportedRequestType if the controller doesn't support the current request type
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequest(F3_FLOW3_MVC_Request $request, F3_FLOW3_MVC_Response $response) {
		if (!$this->canProcessRequest($request)) throw new F3_FLOW3_MVC_Exception_UnsupportedRequestType(get_class($this) . ' does not support requests of type "' . get_class($request) . '"' , 1187701131);

		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;

		$this->mapRequestArgumentsToLocalArguments();
	}

	/**
	 * Forwards the request to another controller.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function forward($actionName, $controllerName = NULL, $packageKey = NULL, F3_FLOW3_MVC_Controller_Arguments $arguments = NULL) {
		$this->request->setDispatched(FALSE);
		$this->request->setControllerActionName($actionName);
		if ($controllerName !== NULL) $this->request->setControllerName($controllerName);
		if ($packageKey !== NULL) $this->request->setControllerPackageKey($packageKey);
		if ($arguments !== NULL) $this->request->setArguments($arguments);
	}

	/**
	 * Returns the arguments which are defined for this controller.
	 *
	 * Use this information if you want to know about what arguments are supported and / or
	 * required by this controller or if you'd like to know about further information about
	 * each argument.
	 *
	 * @return F3_FLOW3_MVC_Controller_Arguments Supported arguments of this controller
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Maps arguments delivered by the request object to the local controller arguments.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function mapRequestArgumentsToLocalArguments() {
		$this->propertyMapper->setTarget($this->arguments);

		foreach ($this->arguments as $argument) {

			if ($argument->getFilter() != NULL) $this->propertyMapper->registerFilter($argument->getFilter());
			if ($argument->getPropertyEditor() != NULL) $this->propertyMapper->registerPropertyEditor($argument->getPropertyEditor(), $argument->getPropertyEditorInputFormat());
		}

		$argumentsValidator = $this->createNewArgumentsValidator($this->arguments);
		$this->propertyMapper->registerValidator($argumentsValidator);
		$this->propertyMapper->setAllowedProperties(array_merge($this->arguments->getArgumentNames(), $this->arguments->getArgumentShortNames()));
		$this->propertyMapper->map(new ArrayObject($this->request->getArguments()));

		$this->argumentMappingResults = $this->propertyMapper->getMappingResults();
		foreach ($this->argumentMappingResults->getErrors() as $propertyName => $error) {
			$this->arguments[$propertyName]->setValidity(FALSE);
		}
	}

	/**
	 * Factory method to create a arguments validator
	 *
	 * @return F3_FLOW3_MVC_Controller_ArgumentsValidator An argument validator
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	protected function createNewArgumentsValidator() {
		return $this->componentFactory->getComponent('F3_FLOW3_MVC_Controller_ArgumentsValidator', $this->arguments);
	}
}

?>