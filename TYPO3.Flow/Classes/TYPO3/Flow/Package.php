<?php
namespace TYPO3\Flow;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Package\Package as BasePackage;

/**
 * The TYPO3 Flow Package
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
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
		$bootstrap->registerRequestHandler(new \TYPO3\Flow\Cli\SlaveRequestHandler($bootstrap));
		$bootstrap->registerRequestHandler(new \TYPO3\Flow\Cli\CommandRequestHandler($bootstrap));
		$bootstrap->registerRequestHandler(new \TYPO3\Flow\Http\RequestHandler($bootstrap));

		if ($bootstrap->getContext()->isTesting()) {
			$bootstrap->getEarlyInstance('TYPO3\Flow\Core\ClassLoader')->setConsiderTestsNamespace(TRUE);
			$bootstrap->registerRequestHandler(new \TYPO3\Flow\Tests\FunctionalTestRequestHandler($bootstrap));
		}

		$bootstrap->registerCompiletimeCommand('typo3.flow:core:*');
		$bootstrap->registerCompiletimeCommand('typo3.flow:cache:flush');

		$dispatcher = $bootstrap->getSignalSlotDispatcher();
		$dispatcher->connect('TYPO3\Flow\Mvc\Dispatcher', 'afterControllerInvocation', 'TYPO3\Flow\Persistence\PersistenceManagerInterface', 'persistAll');
		$dispatcher->connect('TYPO3\Flow\Cli\SlaveRequestHandler', 'dispatchedCommandLineSlaveRequest', 'TYPO3\Flow\Persistence\PersistenceManagerInterface', 'persistAll');
		$dispatcher->connect('TYPO3\Flow\Core\Bootstrap', 'bootstrapShuttingDown', 'TYPO3\Flow\Configuration\ConfigurationManager', 'shutdown');
		$dispatcher->connect('TYPO3\Flow\Core\Bootstrap', 'bootstrapShuttingDown', 'TYPO3\Flow\Object\ObjectManagerInterface', 'shutdown');
		$dispatcher->connect('TYPO3\Flow\Core\Bootstrap', 'bootstrapShuttingDown', 'TYPO3\Flow\Reflection\ReflectionService', 'saveToCache');

		$dispatcher->connect('TYPO3\Flow\Command\CoreCommandController', 'finishedCompilationRun', 'TYPO3\Flow\Security\Policy\PolicyService', 'savePolicyCache');

		$dispatcher->connect('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', 'authenticatedToken', 'TYPO3\Flow\Session\SessionInterface', 'renewId');

		$dispatcher->connect('TYPO3\Flow\Monitor\FileMonitor', 'filesHaveChanged', 'TYPO3\Flow\Cache\CacheManager', 'flushSystemCachesByChangedFiles');

		$dispatcher->connect('TYPO3\Flow\Tests\FunctionalTestCase', 'functionalTestTearDown', 'TYPO3\Flow\Mvc\Routing\Aspect\RouterCachingAspect', 'flushCaches');
	}
}

?>
