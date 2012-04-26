<?php
namespace TYPO3\FLOW3;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Package\Package as BasePackage;

/**
 * The FLOW3 Package
 *
 */
class Package extends BasePackage {

	/**
	 * @var boolean
	 */
	protected $protected = TRUE;

	/**
	 * Invokes custom PHP code directly after the package manager has been initialized.
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(\TYPO3\FLOW3\Core\Bootstrap $bootstrap) {
		$bootstrap->registerRequestHandler(new \TYPO3\FLOW3\Cli\SlaveRequestHandler($bootstrap));
		$bootstrap->registerRequestHandler(new \TYPO3\FLOW3\Cli\CommandRequestHandler($bootstrap));
		$bootstrap->registerRequestHandler(new \TYPO3\FLOW3\Http\RequestHandler($bootstrap));

		if ($bootstrap->getContext() === 'Testing') {
			$bootstrap->getEarlyInstance('TYPO3\FLOW3\Core\ClassLoader')->setConsiderTestsNamespace(TRUE);
			$bootstrap->registerRequestHandler(new \TYPO3\FLOW3\Tests\FunctionalTestRequestHandler($bootstrap));
		}

		$bootstrap->registerCompiletimeCommand('typo3.flow3:core:*');
		$bootstrap->registerCompiletimeCommand('typo3.flow3:cache:flush');

		$dispatcher = $bootstrap->getSignalSlotDispatcher();
		$dispatcher->connect('TYPO3\FLOW3\Core\Bootstrap', 'finishedRuntimeRun', 'TYPO3\FLOW3\Persistence\PersistenceManagerInterface', 'persistAll');
		$dispatcher->connect('TYPO3\FLOW3\Cli\SlaveRequestHandler', 'dispatchedCommandLineSlaveRequest', 'TYPO3\FLOW3\Persistence\PersistenceManagerInterface', 'persistAll');
		$dispatcher->connect('TYPO3\FLOW3\Core\Bootstrap', 'bootstrapShuttingDown', 'TYPO3\FLOW3\Configuration\ConfigurationManager', 'shutdown');
		$dispatcher->connect('TYPO3\FLOW3\Core\Bootstrap', 'bootstrapShuttingDown', 'TYPO3\FLOW3\Object\ObjectManagerInterface', 'shutdown');
		$dispatcher->connect('TYPO3\FLOW3\Core\Bootstrap', 'bootstrapShuttingDown', 'TYPO3\FLOW3\Reflection\ReflectionService', 'saveToCache');

		$dispatcher->connect('TYPO3\FLOW3\Command\CoreCommandController', 'finishedCompilationRun', 'TYPO3\FLOW3\Security\Policy\PolicyService', 'savePolicyCache');

		$dispatcher->connect('TYPO3\FLOW3\Security\Authentication\AuthenticationProviderManager', 'authenticatedToken', 'TYPO3\FLOW3\Session\SessionInterface', 'renewId');
		$dispatcher->connect('TYPO3\FLOW3\Security\Authentication\AuthenticationProviderManager', 'loggedOut', 'TYPO3\FLOW3\Session\SessionInterface', 'destroy');

		$dispatcher->connect('TYPO3\FLOW3\Monitor\FileMonitor', 'filesHaveChanged', 'TYPO3\FLOW3\Cache\CacheManager', 'flushSystemCachesByChangedFiles');
	}
}

?>
