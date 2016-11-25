<?php
namespace Neos\Flow;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\ResourceManagement\ResourceManager;

/**
 * The Flow Package
 */
class Package extends BasePackage
{
    /**
     * @var boolean
     */
    protected $protected = true;

    /**
     * Invokes custom PHP code directly after the package manager has been initialized.
     *
     * @param Core\Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Core\Bootstrap $bootstrap)
    {
        $bootstrap->registerRequestHandler(new Cli\SlaveRequestHandler($bootstrap));
        $bootstrap->registerRequestHandler(new Cli\CommandRequestHandler($bootstrap));
        $bootstrap->registerRequestHandler(new Http\RequestHandler($bootstrap));

        if ($bootstrap->getContext()->isTesting()) {
            $bootstrap->registerRequestHandler(new Tests\FunctionalTestRequestHandler($bootstrap));
        }

        $bootstrap->registerCompiletimeCommand('neos.flow:core:*');
        $bootstrap->registerCompiletimeCommand('neos.flow:cache:flush');
        $bootstrap->registerCompiletimeCommand('neos.flow:package:rescan');

        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(\Neos\Flow\Mvc\Dispatcher::class, 'afterControllerInvocation', function ($request) use ($bootstrap) {
            if ($bootstrap->getObjectManager()->hasInstance(\Neos\Flow\Persistence\PersistenceManagerInterface::class)) {
                if (!$request instanceof Mvc\ActionRequest || $request->getHttpRequest()->isMethodSafe() !== true) {
                    $bootstrap->getObjectManager()->get(\Neos\Flow\Persistence\PersistenceManagerInterface::class)->persistAll();
                } elseif ($request->getHttpRequest()->isMethodSafe()) {
                    $bootstrap->getObjectManager()->get(\Neos\Flow\Persistence\PersistenceManagerInterface::class)->persistAll(true);
                }
            }
        });
        $dispatcher->connect(\Neos\Flow\Cli\SlaveRequestHandler::class, 'dispatchedCommandLineSlaveRequest', \Neos\Flow\Persistence\PersistenceManagerInterface::class, 'persistAll');

        $context = $bootstrap->getContext();
        if (!$context->isProduction()) {
            $dispatcher->connect(\Neos\Flow\Core\Booting\Sequence::class, 'afterInvokeStep', function ($step) use ($bootstrap, $dispatcher) {
                if ($step->getIdentifier() === 'neos.flow:resources') {
                    $publicResourcesFileMonitor = \Neos\Flow\Monitor\FileMonitor::createFileMonitorAtBoot('Flow_PublicResourcesFiles', $bootstrap);
                    $packageManager = $bootstrap->getEarlyInstance(\Neos\Flow\Package\PackageManagerInterface::class);
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

        $publishResources = function ($identifier, $changedFiles) use ($bootstrap) {
            if ($identifier !== 'Flow_PublicResourcesFiles') {
                return;
            }
            $objectManager = $bootstrap->getObjectManager();
            $resourceManager = $objectManager->get(\Neos\Flow\ResourceManagement\ResourceManager::class);
            $resourceManager->getCollection(ResourceManager::DEFAULT_STATIC_COLLECTION_NAME)->publish();
        };

        $dispatcher->connect(\Neos\Flow\Monitor\FileMonitor::class, 'filesHaveChanged', $publishResources);

        $dispatcher->connect(\Neos\Flow\Core\Bootstrap::class, 'bootstrapShuttingDown', \Neos\Flow\Configuration\ConfigurationManager::class, 'shutdown');
        $dispatcher->connect(\Neos\Flow\Core\Bootstrap::class, 'bootstrapShuttingDown', \Neos\Flow\ObjectManagement\ObjectManagerInterface::class, 'shutdown');

        $dispatcher->connect(\Neos\Flow\Core\Bootstrap::class, 'bootstrapShuttingDown', \Neos\Flow\Reflection\ReflectionService::class, 'saveToCache');

        $dispatcher->connect(\Neos\Flow\Command\CoreCommandController::class, 'finishedCompilationRun', \Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegePointcutFilter::class, 'savePolicyCache');
        $dispatcher->connect(\Neos\Flow\Command\CoreCommandController::class, 'finishedCompilationRun', \Neos\Flow\Aop\Pointcut\RuntimeExpressionEvaluator::class, 'saveRuntimeExpressions');

        $dispatcher->connect(\Neos\Flow\Security\Authentication\AuthenticationProviderManager::class, 'authenticatedToken', function () use ($bootstrap) {
            $session = $bootstrap->getObjectManager()->get(\Neos\Flow\Session\SessionInterface::class);
            if ($session->isStarted()) {
                $session->renewId();
            }
        });

        $dispatcher->connect(\Neos\Flow\Monitor\FileMonitor::class, 'filesHaveChanged', \Neos\Flow\Cache\CacheManager::class, 'flushSystemCachesByChangedFiles');

        $dispatcher->connect(\Neos\Flow\Tests\FunctionalTestCase::class, 'functionalTestTearDown', \Neos\Flow\Mvc\Routing\RouterCachingService::class, 'flushCaches');

        $dispatcher->connect(\Neos\Flow\Configuration\ConfigurationManager::class, 'configurationManagerReady', function (Configuration\ConfigurationManager $configurationManager) {
            $configurationManager->registerConfigurationType('Views', Configuration\ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_APPEND);
        });
        $dispatcher->connect(\Neos\Flow\Command\CacheCommandController::class, 'warmupCaches', \Neos\Flow\Configuration\ConfigurationManager::class, 'warmup');

        $dispatcher->connect(\Neos\Flow\Package\PackageManager::class, 'packageStatesUpdated', function () use ($dispatcher) {
            $dispatcher->connect(\Neos\Flow\Core\Bootstrap::class, 'bootstrapShuttingDown', \Neos\Flow\Cache\CacheManager::class, 'flushCaches');
        });
    }
}
