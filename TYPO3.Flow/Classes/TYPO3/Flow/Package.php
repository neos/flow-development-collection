<?php
namespace TYPO3\Flow;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Package\Package as BasePackage;
use TYPO3\Flow\Package\PackageInterface;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Fluid\Core\Parser\TemplateParser;

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

        $bootstrap->registerCompiletimeCommand('typo3.flow:core:*');
        $bootstrap->registerCompiletimeCommand('typo3.flow:cache:flush');
        $bootstrap->registerCompiletimeCommand('typo3.flow:package:rescan');

        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(\TYPO3\Flow\Mvc\Dispatcher::class, 'afterControllerInvocation', function ($request) use ($bootstrap) {
            if ($bootstrap->getObjectManager()->hasInstance(\TYPO3\Flow\Persistence\PersistenceManagerInterface::class)) {
                if (!$request instanceof Mvc\ActionRequest || $request->getHttpRequest()->isMethodSafe() !== true) {
                    $bootstrap->getObjectManager()->get(\TYPO3\Flow\Persistence\PersistenceManagerInterface::class)->persistAll();
                } elseif ($request->getHttpRequest()->isMethodSafe()) {
                    $bootstrap->getObjectManager()->get(\TYPO3\Flow\Persistence\PersistenceManagerInterface::class)->persistAll(true);
                }
            }
        });
        $dispatcher->connect(\TYPO3\Flow\Cli\SlaveRequestHandler::class, 'dispatchedCommandLineSlaveRequest', \TYPO3\Flow\Persistence\PersistenceManagerInterface::class, 'persistAll');

        $context = $bootstrap->getContext();
        if (!$context->isProduction()) {
            $dispatcher->connect(\TYPO3\Flow\Core\Booting\Sequence::class, 'afterInvokeStep', function ($step) use ($bootstrap, $dispatcher) {
                if ($step->getIdentifier() === 'typo3.flow:resources') {
                    $publicResourcesFileMonitor = \TYPO3\Flow\Monitor\FileMonitor::createFileMonitorAtBoot('Flow_PublicResourcesFiles', $bootstrap);
                    $packageManager = $bootstrap->getEarlyInstance(\TYPO3\Flow\Package\PackageManagerInterface::class);
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
            $resourceManager = $objectManager->get(\TYPO3\Flow\Resource\ResourceManager::class);
            $resourceManager->getCollection(ResourceManager::DEFAULT_STATIC_COLLECTION_NAME)->publish();
        };

        $dispatcher->connect(\TYPO3\Flow\Monitor\FileMonitor::class, 'filesHaveChanged', $publishResources);

        $dispatcher->connect(\TYPO3\Flow\Core\Bootstrap::class, 'bootstrapShuttingDown', \TYPO3\Flow\Configuration\ConfigurationManager::class, 'shutdown');
        $dispatcher->connect(\TYPO3\Flow\Core\Bootstrap::class, 'bootstrapShuttingDown', \TYPO3\Flow\Object\ObjectManagerInterface::class, 'shutdown');

        $dispatcher->connect(\TYPO3\Flow\Core\Bootstrap::class, 'bootstrapShuttingDown', \TYPO3\Flow\Reflection\ReflectionService::class, 'saveToCache');

        $dispatcher->connect(\TYPO3\Flow\Command\CoreCommandController::class, 'finishedCompilationRun', \TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegePointcutFilter::class, 'savePolicyCache');
        $dispatcher->connect(\TYPO3\Flow\Command\CoreCommandController::class, 'finishedCompilationRun', \TYPO3\Flow\Aop\Pointcut\RuntimeExpressionEvaluator::class, 'saveRuntimeExpressions');

        $dispatcher->connect(\TYPO3\Flow\Security\Authentication\AuthenticationProviderManager::class, 'authenticatedToken', function () use ($bootstrap) {
            $session = $bootstrap->getObjectManager()->get(\TYPO3\Flow\Session\SessionInterface::class);
            if ($session->isStarted()) {
                $session->renewId();
            }
        });

        $dispatcher->connect(\TYPO3\Flow\Monitor\FileMonitor::class, 'filesHaveChanged', \TYPO3\Flow\Cache\CacheManager::class, 'flushSystemCachesByChangedFiles');

        $dispatcher->connect(\TYPO3\Flow\Tests\FunctionalTestCase::class, 'functionalTestTearDown', \TYPO3\Flow\Mvc\Routing\RouterCachingService::class, 'flushCaches');

        $dispatcher->connect(\TYPO3\Flow\Configuration\ConfigurationManager::class, 'configurationManagerReady', function (Configuration\ConfigurationManager $configurationManager) {
            $configurationManager->registerConfigurationType('Views', Configuration\ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_APPEND);
        });
        $dispatcher->connect(\TYPO3\Flow\Command\CacheCommandController::class, 'warmupCaches', \TYPO3\Flow\Configuration\ConfigurationManager::class, 'warmup');

        $dispatcher->connect(\TYPO3\Fluid\Core\Parser\TemplateParser::class, 'initializeNamespaces', function (TemplateParser $templateParser) use ($bootstrap) {
            /** @var PackageManagerInterface $packageManager */
            $packageManager = $bootstrap->getEarlyInstance(\TYPO3\Flow\Package\PackageManagerInterface::class);
            /** @var PackageInterface $package */
            foreach ($packageManager->getActivePackages() as $package) {
                $templateParser->registerNamespace(strtolower($package->getPackageKey()), $package->getNamespace() . '\\ViewHelpers');
            }
        });

        $dispatcher->connect(\TYPO3\Flow\Package\PackageManager::class, 'packageStatesUpdated', function () use ($dispatcher, $bootstrap) {
            $dispatcher->connect(\TYPO3\Flow\Core\Bootstrap::class, 'bootstrapShuttingDown', function () use ($bootstrap) {
                $bootstrap->getObjectManager()->get(\TYPO3\Flow\Cache\CacheManager::class)->flushCaches();
            });
        });
    }
}
