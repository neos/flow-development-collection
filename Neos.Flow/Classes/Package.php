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
        $context = $bootstrap->getContext();

        if (PHP_SAPI === 'cli') {
            $bootstrap->registerRequestHandler(new Cli\SlaveRequestHandler($bootstrap));
            $bootstrap->registerRequestHandler(new Cli\CommandRequestHandler($bootstrap));
        } else {
            $bootstrap->registerRequestHandler(new Http\RequestHandler($bootstrap));
        }

        if ($context->isTesting()) {
            $bootstrap->registerRequestHandler(new Tests\FunctionalTestRequestHandler($bootstrap));
        }

        $bootstrap->registerCompiletimeCommand('neos.flow:core:*');
        $bootstrap->registerCompiletimeCommand('neos.flow:cache:flush');
        $bootstrap->registerCompiletimeCommand('neos.flow:package:rescan');

        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(Mvc\Dispatcher::class, 'afterControllerInvocation', function ($request) use ($bootstrap) {
            if ($bootstrap->getObjectManager()->hasInstance(Persistence\PersistenceManagerInterface::class)) {
                if (!$request instanceof Mvc\ActionRequest || $request->getHttpRequest()->isMethodSafe() !== true) {
                    $bootstrap->getObjectManager()->get(Persistence\PersistenceManagerInterface::class)->persistAll();
                } elseif ($request->getHttpRequest()->isMethodSafe()) {
                    $bootstrap->getObjectManager()->get(Persistence\PersistenceManagerInterface::class)->persistAll(true);
                }
            }
        });
        $dispatcher->connect(Cli\SlaveRequestHandler::class, 'dispatchedCommandLineSlaveRequest', Persistence\PersistenceManagerInterface::class, 'persistAll');

        if (!$context->isProduction()) {
            $dispatcher->connect(Core\Booting\Sequence::class, 'afterInvokeStep', function ($step) use ($bootstrap, $dispatcher) {
                if ($step->getIdentifier() === 'neos.flow:resources') {
                    $publicResourcesFileMonitor = Monitor\FileMonitor::createFileMonitorAtBoot('Flow_PublicResourcesFiles', $bootstrap);
                    $packageManager = $bootstrap->getEarlyInstance(Package\PackageManagerInterface::class);
                    foreach ($packageManager->getAvailablePackages() as $packageKey => $package) {
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

            $publishResources = function ($identifier, $changedFiles) use ($bootstrap) {
                if ($identifier !== 'Flow_PublicResourcesFiles') {
                    return;
                }
                $objectManager = $bootstrap->getObjectManager();
                $resourceManager = $objectManager->get(ResourceManager::class);
                $resourceManager->getCollection(ResourceManager::DEFAULT_STATIC_COLLECTION_NAME)->publish();
            };

            $dispatcher->connect(Monitor\FileMonitor::class, 'filesHaveChanged', $publishResources);

            $dispatcher->connect(Monitor\FileMonitor::class, 'filesHaveChanged', Cache\CacheManager::class, 'flushSystemCachesByChangedFiles');
        }

        $dispatcher->connect(Core\Bootstrap::class, 'bootstrapShuttingDown', Configuration\ConfigurationManager::class, 'shutdown');
        $dispatcher->connect(Core\Bootstrap::class, 'bootstrapShuttingDown', ObjectManagement\ObjectManagerInterface::class, 'shutdown');

        $dispatcher->connect(Core\Bootstrap::class, 'bootstrapShuttingDown', Reflection\ReflectionService::class, 'saveToCache');

        $dispatcher->connect(Command\CoreCommandController::class, 'finishedCompilationRun', Security\Authorization\Privilege\Method\MethodPrivilegePointcutFilter::class, 'savePolicyCache');

        $dispatcher->connect(Security\Authentication\AuthenticationProviderManager::class, 'authenticatedToken', function () use ($bootstrap) {
            $session = $bootstrap->getObjectManager()->get(Session\SessionInterface::class);
            if ($session->isStarted()) {
                $session->renewId();
            }
        });

        $dispatcher->connect(Tests\FunctionalTestCase::class, 'functionalTestTearDown', Mvc\Routing\RouterCachingService::class, 'flushCaches');

        $dispatcher->connect(Configuration\ConfigurationManager::class, 'configurationManagerReady', function (Configuration\ConfigurationManager $configurationManager) {
            $configurationManager->registerConfigurationType('Views', Configuration\ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_APPEND);
        });
        $dispatcher->connect(Command\CacheCommandController::class, 'warmupCaches', Configuration\ConfigurationManager::class, 'warmup');

        $dispatcher->connect(Package\PackageManager::class, 'packageStatesUpdated', function () use ($dispatcher, $bootstrap) {
            $dispatcher->connect(Core\Bootstrap::class, 'bootstrapShuttingDown', function () use ($bootstrap) {
                $bootstrap->getObjectManager()->get(Cache\CacheManager::class)->flushCaches();
            });
        });

        $dispatcher->connect(Persistence\Doctrine\EntityManagerFactory::class, 'beforeDoctrineEntityManagerCreation', Persistence\Doctrine\EntityManagerConfiguration::class, 'configureEntityManager');
        $dispatcher->connect(Persistence\Doctrine\EntityManagerFactory::class, 'afterDoctrineEntityManagerCreation', Persistence\Doctrine\EntityManagerConfiguration::class, 'enhanceEntityManager');
    }
}
