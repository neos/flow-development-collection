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
use TYPO3\Flow\Package\PackageInterface;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Fluid\Core\Parser\TemplateParser;

/**
 * The TYPO3 Flow Package
 */
class Package extends BasePackage {

	/**
	 * @var boolean
	 */
	protected $protected = TRUE;

	/**
	 * Invokes custom PHP code directly after the package manager has been initialized.
	 *
	 * @param Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(Core\Bootstrap $bootstrap) {
		$bootstrap->registerRequestHandler(new Cli\SlaveRequestHandler($bootstrap));
		$bootstrap->registerRequestHandler(new Cli\CommandRequestHandler($bootstrap));
		$bootstrap->registerRequestHandler(new Http\RequestHandler($bootstrap));

		if ($bootstrap->getContext()->isTesting()) {
			$bootstrap->registerRequestHandler(new Tests\FunctionalTestRequestHandler($bootstrap));
		}

		$bootstrap->registerCompiletimeCommand('typo3.flow:core:*');
		$bootstrap->registerCompiletimeCommand('typo3.flow:cache:flush');

		$dispatcher = $bootstrap->getSignalSlotDispatcher();
		$dispatcher->connect('TYPO3\Flow\Mvc\Dispatcher', 'afterControllerInvocation', function($request) use($bootstrap) {
			if (!$request instanceof Mvc\ActionRequest || $request->getHttpRequest()->isMethodSafe() !== TRUE) {
				$bootstrap->getObjectManager()->get('TYPO3\Flow\Persistence\PersistenceManagerInterface')->persistAll();
			} elseif ($request->getHttpRequest()->isMethodSafe()) {
				$bootstrap->getObjectManager()->get('TYPO3\Flow\Persistence\PersistenceManagerInterface')->persistAll(TRUE);
			}
		});
		$dispatcher->connect('TYPO3\Flow\Cli\SlaveRequestHandler', 'dispatchedCommandLineSlaveRequest', 'TYPO3\Flow\Persistence\PersistenceManagerInterface', 'persistAll');

		$context = $bootstrap->getContext();
		if (!$context->isProduction()) {
			$dispatcher->connect('TYPO3\Flow\Core\Booting\Sequence', 'afterInvokeStep', function ($step) use ($bootstrap, $dispatcher) {
				if ($step->getIdentifier() === 'typo3.flow:resources') {
					$publicResourcesFileMonitor = \TYPO3\Flow\Monitor\FileMonitor::createFileMonitorAtBoot('Flow_PublicResourcesFiles', $bootstrap);
					$packageManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface');
					foreach ($packageManager->getActivePackages() as $packageKey => $package) {
						if ($packageManager->isPackageFrozen($packageKey)) {
							continue;
						}

						$publicResourcesPath = $package->getResourcesPath() . 'Public/';
						if (is_dir($publicResourcesPath)) {
							$publicResourcesFileMonitor->monitorDirectory($publicResourcesPath);
						}
					}
					$publicResourcesFileMonitor->detectChanges();
					$publicResourcesFileMonitor->shutdownObject();
				}
			});
		}

		$publishResources = function($identifier, $changedFiles) use ($bootstrap) {
			if ($identifier !== 'Flow_PublicResourcesFiles') {
				return;
			}
			$objectManager = $bootstrap->getObjectManager();
			$resourceManager = $objectManager->get('TYPO3\Flow\Resource\ResourceManager');
			$resourceManager->getCollection(ResourceManager::DEFAULT_STATIC_COLLECTION_NAME)->publish();
		};

		$dispatcher->connect('TYPO3\Flow\Monitor\FileMonitor', 'filesHaveChanged', $publishResources);

		$dispatcher->connect('TYPO3\Flow\Core\Bootstrap', 'bootstrapShuttingDown', 'TYPO3\Flow\Configuration\ConfigurationManager', 'shutdown');
		$dispatcher->connect('TYPO3\Flow\Core\Bootstrap', 'bootstrapShuttingDown', 'TYPO3\Flow\Object\ObjectManagerInterface', 'shutdown');

		$dispatcher->connect('TYPO3\Flow\Core\Bootstrap', 'bootstrapShuttingDown', 'TYPO3\Flow\Reflection\ReflectionService', 'saveToCache');

		$dispatcher->connect('TYPO3\Flow\Command\CoreCommandController', 'finishedCompilationRun', 'TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegePointcutFilter', 'savePolicyCache');

		$dispatcher->connect('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', 'authenticatedToken', function() use($bootstrap) {
			$session = $bootstrap->getObjectManager()->get('TYPO3\Flow\Session\SessionInterface');
			if ($session->isStarted()) {
				$session->renewId();
			}
		});

		$dispatcher->connect('TYPO3\Flow\Monitor\FileMonitor', 'filesHaveChanged', 'TYPO3\Flow\Cache\CacheManager', 'flushSystemCachesByChangedFiles');

		$dispatcher->connect('TYPO3\Flow\Tests\FunctionalTestCase', 'functionalTestTearDown', 'TYPO3\Flow\Mvc\Routing\RouterCachingService', 'flushCaches');

		$dispatcher->connect('TYPO3\Flow\Configuration\ConfigurationManager', 'configurationManagerReady', function(Configuration\ConfigurationManager $configurationManager) {
			$configurationManager->registerConfigurationType('Views', Configuration\ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_APPEND);
		});
		$dispatcher->connect('TYPO3\Flow\Command\CacheCommandController', 'warmupCaches', 'TYPO3\Flow\Configuration\ConfigurationManager', 'warmup');

		$dispatcher->connect('TYPO3\Fluid\Core\Parser\TemplateParser', 'initializeNamespaces', function(TemplateParser $templateParser) use($bootstrap) {
			/** @var PackageManagerInterface $packageManager */
			$packageManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface');
			/** @var PackageInterface $package */
			foreach ($packageManager->getActivePackages() as $package) {
				$templateParser->registerNamespace(strtolower($package->getPackageKey()), $package->getNamespace() . '\\ViewHelpers');
			}
		});
	}
}
