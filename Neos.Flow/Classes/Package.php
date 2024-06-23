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

use Neos\Flow\Annotations\Route;
use Neos\Flow\Cache\AnnotationsCacheFlusher;
use Neos\Flow\Configuration\Loader\AppendLoader;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\Booting\Step;
use Neos\Flow\Http\Helper\SecurityHelper;
use Neos\Flow\ObjectManagement\Proxy;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Flow\Security\Authentication\AuthenticationProviderManager;
use Neos\Flow\Security\Authentication\Token\SessionlessTokenInterface;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Cryptography\PrecomposedHashProvider;

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
            // TODO: This is technically not necessary as we can register the request handler in the functional bootstrap
            // A future commit will remove this aftter BuildEssentials is adapted
            /** @phpstan-ignore-next-line composer doesnt autoload this class */
            $bootstrap->registerRequestHandler(new Tests\FunctionalTestRequestHandler($bootstrap));
        }

        $bootstrap->registerCompiletimeCommand('neos.flow:core:*');
        $bootstrap->registerCompiletimeCommand('neos.flow:cache:flush');
        $bootstrap->registerCompiletimeCommand('neos.flow:package:rescan');

        $dispatcher = $bootstrap->getSignalSlotDispatcher();

        $dispatcher->connect(Mvc\Dispatcher::class, 'afterControllerInvocation', function ($request) use ($bootstrap) {
            // No auto-persistence if there is no PersistenceManager registered
            if (
                $bootstrap->getObjectManager()->has(Persistence\PersistenceManagerInterface::class)
            ) {
                if (!$request instanceof Mvc\ActionRequest || SecurityHelper::hasSafeMethod($request->getHttpRequest()) !== true) {
                    $bootstrap->getObjectManager()->get(Persistence\PersistenceManagerInterface::class)->persistAll();
                } elseif (SecurityHelper::hasSafeMethod($request->getHttpRequest())) {
                    /** @phpstan-ignore-next-line the persistence manager interface doesn't specify this method */
                    $bootstrap->getObjectManager()->get(Persistence\PersistenceManagerInterface::class)->persistAllowedObjects();
                }
            }
        });
        $dispatcher->connect(Cli\SlaveRequestHandler::class, 'dispatchedCommandLineSlaveRequest', Persistence\PersistenceManagerInterface::class, 'persistAll', false);

        $dispatcher->connect(Command\CacheCommandController::class, 'warmupCaches', PrecomposedHashProvider::class, 'precomposeHash');

        if (!$context->isProduction()) {
            $dispatcher->connect(Core\Booting\Sequence::class, 'afterInvokeStep', function (Step $step) use ($bootstrap) {
                if ($step->getIdentifier() === 'neos.flow:resources') {
                    $publicResourcesFileMonitor = Monitor\FileMonitor::createFileMonitorAtBoot('Flow_PublicResourcesFiles', $bootstrap);
                    /** @var PackageManager $packageManager */
                    $packageManager = $bootstrap->getEarlyInstance(Package\PackageManager::class);
                    foreach ($packageManager->getFlowPackages() as $packageKey => $package) {
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

            $publishResources = function ($identifier) use ($bootstrap) {
                if ($identifier !== 'Flow_PublicResourcesFiles') {
                    return;
                }
                $objectManager = $bootstrap->getObjectManager();
                $resourceManager = $objectManager->get(ResourceManager::class);
                if ($staticCollection = $resourceManager->getCollection(ResourceManager::DEFAULT_STATIC_COLLECTION_NAME)) {
                    $staticCollection->publish();
                }
            };

            $dispatcher->connect(Monitor\FileMonitor::class, 'filesHaveChanged', $publishResources);

            $dispatcher->connect(Monitor\FileMonitor::class, 'filesHaveChanged', Cache\CacheManager::class, 'flushSystemCachesByChangedFiles');
        }

        // The ObjectManager has to be shutdown before the ConfigurationManager, see https://github.com/neos/flow-development-collection/issues/2183
        $dispatcher->connect(Core\Bootstrap::class, 'bootstrapShuttingDown', ObjectManagement\ObjectManagerInterface::class, 'shutdown');
        $dispatcher->connect(Core\Bootstrap::class, 'bootstrapShuttingDown', Configuration\ConfigurationManager::class, 'shutdown');

        $dispatcher->connect(Core\Bootstrap::class, 'bootstrapShuttingDown', Reflection\ReflectionService::class, 'saveToCache');

        $dispatcher->connect(Command\CoreCommandController::class, 'finishedCompilationRun', Security\Authorization\Privilege\Method\MethodPrivilegePointcutFilter::class, 'savePolicyCache');

        $dispatcher->connect(Security\Authentication\AuthenticationProviderManager::class, 'authenticatedToken', function (TokenInterface $token) use ($bootstrap) {
            $session = $bootstrap->getObjectManager()->get(Session\SessionInterface::class);
            if ($session->isStarted() && !$token instanceof SessionlessTokenInterface) {
                $session->renewId();
            }
        });

        /** @phpstan-ignore-next-line composer doesnt autoload this class */
        $dispatcher->connect(Tests\FunctionalTestCase::class, 'functionalTestTearDown', Mvc\Routing\RouterCachingService::class, 'flushCaches');

        $dispatcher->connect(Configuration\ConfigurationManager::class, 'configurationManagerReady', function (Configuration\ConfigurationManager $configurationManager) {
            $configurationManager->registerConfigurationType('Views', new AppendLoader(new YamlSource(), 'Views'));
        });
        $dispatcher->connect(Command\CacheCommandController::class, 'warmupCaches', Configuration\ConfigurationManager::class, 'warmup');

        $dispatcher->connect(Package\PackageManager::class, 'packageStatesUpdated', function () use ($dispatcher, $bootstrap) {
            $dispatcher->connect(Core\Bootstrap::class, 'bootstrapShuttingDown', function () use ($bootstrap) {
                $bootstrap->getObjectManager()->get(Cache\CacheManager::class)->flushCaches();
            });
        });

        $dispatcher->connect(Persistence\Doctrine\EntityManagerFactory::class, 'beforeDoctrineEntityManagerCreation', Persistence\Doctrine\EntityManagerConfiguration::class, 'configureEntityManager');
        $dispatcher->connect(Persistence\Doctrine\EntityManagerFactory::class, 'afterDoctrineEntityManagerCreation', Persistence\Doctrine\EntityManagerConfiguration::class, 'enhanceEntityManager');

        $dispatcher->connect(Persistence\Doctrine\PersistenceManager::class, 'allObjectsPersisted', ResourceRepository::class, 'resetAfterPersistingChanges');

        $dispatcher->connect(AuthenticationProviderManager::class, 'successfullyAuthenticated', Context::class, 'refreshRoles');
        $dispatcher->connect(AuthenticationProviderManager::class, 'loggedOut', Context::class, 'refreshTokens');

        $dispatcher->connect(Proxy\Compiler::class, 'compiledClasses', function (array $classNames) use ($bootstrap) {
            $annotationsCacheFlusher = $bootstrap->getObjectManager()->get(AnnotationsCacheFlusher::class);
            $annotationsCacheFlusher->registerAnnotation(Route::class, ['Flow_Mvc_Routing_Route', 'Flow_Mvc_Routing_Resolve']);
            $annotationsCacheFlusher->flushConfigurationCachesByCompiledClass($classNames);
        });
    }
}
