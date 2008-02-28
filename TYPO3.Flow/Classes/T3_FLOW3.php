<?php
declare(ENCODING="utf-8");

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

define('TYPO3_PATH_PUBLICFILECACHE', TYPO3_PATH_ROOT . 'FileCache/Public/');
define('TYPO3_PATH_PRIVATEFILECACHE', TYPO3_PATH_ROOT . 'FileCache/Private/');
define('TYPO3_PATH_PACKAGES', TYPO3_PATH_ROOT . 'Packages/');
define('TYPO3_PATH_FLOW3', TYPO3_PATH_PACKAGES . 'FLOW3/Classes/' );

/**
 * @package FLOW3
 * @version $Id: $
 */

/**
 * General purpose central core hyper FLOW3 class
 *
 * @package FLOW3
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
final class T3_FLOW3 {

	const MINIMUM_PHP_VERSION = '5.2.0';
	const MAXIMUM_PHP_VERSION = '5.9.9';

	/**
	 * @var T3_FLOW3_Component_ManagerInterface An instance of the component manager
	 */
	protected $componentManager;

	/**
	 * @var boolean Flag to determine if the initialize() method has been called already
	 */
	protected $isInitialized = FALSE;

	/**
	 * Constructor
	 *
	 * @param string $context The application context
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal RL: The version check should be replaced by a more fine grained check done by the package manager, taking the package's requirements into account.
	 */
	public function __construct($context = 'Development') {
		$this->checkEnvironment();

		require_once(TYPO3_PATH_FLOW3 . 'Error/T3_FLOW3_Error_ErrorHandler.php');
		require_once(TYPO3_PATH_FLOW3 . 'Error/T3_FLOW3_Error_ExceptionHandler.php');
		require_once(TYPO3_PATH_FLOW3 . 'Resource/T3_FLOW3_Resource_Manager.php');

		$errorHandler = new T3_FLOW3_Error_ErrorHandler();
		$exceptionHandler = new T3_FLOW3_Error_ExceptionHandler();
		$resourceManager = new T3_FLOW3_Resource_Manager();

		set_error_handler(array($errorHandler, 'handleError'));

		$resourceManager->registerClassFile('T3_FLOW3_Exception', TYPO3_PATH_FLOW3 . 'T3_FLOW3_Exception.php');
		$resourceManager->registerClassFile('T3_FLOW3_Component_Manager', TYPO3_PATH_FLOW3 . 'Component/T3_FLOW3_Component_Manager.php');
		$resourceManager->registerClassFile('T3_FLOW3_AOP_Framework', TYPO3_PATH_FLOW3 . 'AOP/T3_FLOW3_AOP_Framework.php');
		$resourceManager->registerClassFile('T3_FLOW3_Package_Manager', TYPO3_PATH_FLOW3 . 'Package/T3_FLOW3_Package_Manager.php');

		$this->componentManager = new T3_FLOW3_Component_Manager();
		$this->componentManager->setContext($context);
		$this->componentManager->registerComponent('T3_FLOW3_Resource_ManagerInterface', 'T3_FLOW3_Resource_Manager', $resourceManager);
		$this->componentManager->registerComponent('T3_FLOW3_AOP_Framework', 'T3_FLOW3_AOP_Framework');
		$this->componentManager->registerComponent('T3_FLOW3_Package_ManagerInterface', 'T3_FLOW3_Package_Manager');
	}

	/**
	 * Explicitly initializes the FLOW3 Framework - that is the underlying parts as the component- and package manager as well
	 * as the AOP framework.
	 *
	 * Usually this method is only called from unit tests or other applications which need a more fine grained control over
	 * the initialization and request handling process. Most other applications just call the run() method.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see run()
	 * @throws T3_FLOW3_Exception if the framework has already been initialized.
	 */
	public function initialize() {
		if ($this->isInitialized) throw new T3_FLOW3_Exception('FLOW3 has already been initialized!', 1169546671);

		$this->componentManager->getComponent('T3_FLOW3_Package_ManagerInterface')->initialize();
		$this->componentManager->getComponent('T3_FLOW3_AOP_Framework')->initialize();
		$this->componentManager->getComponent('T3_FLOW3_Utility_Environment');
		$this->isInitialized = TRUE;
	}

	/**
	 * Runs the the TYPO3 Framework by resolving an appropriate Request Handler and passing control to it.
	 * If the Framework is not initialized yet, it will be initialized.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function run() {
		if (!$this->isInitialized) $this->initialize();
		$requestHandler = $this->componentManager->getComponent('T3_FLOW3_MVC_RequestHandlerResolver')->resolveRequestHandler();
		$requestHandler->handleRequest();
	}

	/**
	 * Returns an instance of the active component manager. This method is and should only
	 * be used by unit tests as long as no Dependency Injection is supported. In almost any other
	 * case, a reference to the component manager can be injected
	 *
 	 * @return	T3_FLOW3_Component_ManagerInterface
 	 * @author	Robert Lemke <robert@typo3.org>
	 */
	public function getComponentManager() {
		return $this->componentManager;
	}

	/**
	 * Checks PHP version and other parameters of the environment
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function checkEnvironment() {
		if (extension_loaded('eAccelerator')) {
			eaccelerator_caching(FALSE);
			eaccelerator_clear();
		}
		if (version_compare(phpversion(), self::MINIMUM_PHP_VERSION, '<')) {
			die ('FLOW3 requires PHP version ' . self::MINIMUM_PHP_VERSION . ' or higher but your installed version is currently ' . phpversion() . '. (Error #1172215790)');
		}
		if (version_compare(phpversion(), self::MAXIMUM_PHP_VERSION, '>')) {
			die ('FLOW3 requires PHP version ' . self::MAXIMUM_PHP_VERSION . ' or lower but your installed version is currently ' . phpversion() . '. (Error #1172215790)');
		}
		set_time_limit(0);
		ini_set('unicode.output_encoding', 'utf-8');
		ini_set('unicode.stream_encoding', 'utf-8');
		ini_set('unicode.runtime_encoding', 'utf-8');
#		locale_set_default('en_UK');
		if (ini_get('date.timezone') == '') {
			date_default_timezone_set('Europe/Copenhagen');
		}
		preg_match('/(?P<character>\w),/', 'a,b,c,d', $matches);
		if (!isset($matches[(string)'character'])) die('Your PHP6 version is buggy and produces binary array indexes - please use a more recent snapshot of PHP6.');
	}
}

?>