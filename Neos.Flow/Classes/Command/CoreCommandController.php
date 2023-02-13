<?php
namespace Neos\Flow\Command;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\Builder\ProxyClassBuilder as AopProxyClassBuilder;
use Neos\Cache\Backend\FreezableBackendInterface;
use Neos\Flow\Cache\CacheManager;
use Neos\Cache\Frontend\PhpFrontend;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\Dispatcher;
use Neos\Flow\Cli\RequestBuilder;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Log\PsrLoggerFactoryInterface;
use Neos\Flow\ObjectManagement\DependencyInjection\ProxyClassBuilder;
use Neos\Flow\ObjectManagement\Proxy\Compiler;
use Neos\Flow\SignalSlot\Dispatcher as SignalSlotDispatcher;
use Neos\Flow\Utility\Environment;
use Neos\Utility\Files;

/**
 * Command controller for core commands
 *
 * NOTE: This command controller will run in compile time (as defined in the package bootstrap)
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class CoreCommandController extends CommandController
{
    /**
     * @var RequestBuilder
     */
    protected $requestBuilder;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var SignalSlotDispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var Compiler
     */
    protected $proxyClassCompiler;

    /**
     * @var AopProxyClassBuilder
     */
    protected $aopProxyClassBuilder;

    /**
     * @var ProxyClassBuilder
     */
    protected $dependencyInjectionProxyClassBuilder;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @param RequestBuilder $requestBuilder
     * @return void
     */
    public function injectRequestBuilder(RequestBuilder $requestBuilder)
    {
        $this->requestBuilder = $requestBuilder;
    }

    /**
     * @param Dispatcher $dispatcher
     * @return void
     */
    public function injectDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param SignalSlotDispatcher $signalSlotDispatcher
     * @return void
     */
    public function injectSignalSlotDispatcher(SignalSlotDispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * @param Bootstrap $bootstrap
     * @return void
     */
    public function injectBootstrap(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * @param CacheManager $cacheManager
     * @return void
     */
    public function injectCacheManager(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * @param Compiler $proxyClassCompiler
     * @return void
     */
    public function injectProxyClassCompiler(Compiler $proxyClassCompiler)
    {
        $this->proxyClassCompiler = $proxyClassCompiler;
    }

    /**
     * @param AopProxyClassBuilder $aopProxyClassBuilder
     * @return void
     */
    public function injectAopProxyClassBuilder(AopProxyClassBuilder $aopProxyClassBuilder)
    {
        $this->aopProxyClassBuilder = $aopProxyClassBuilder;
    }

    /**
     * @param ProxyClassBuilder $dependencyInjectionProxyClassBuilder
     * @return void
     */
    public function injectDependencyInjectionProxyClassBuilder(ProxyClassBuilder $dependencyInjectionProxyClassBuilder)
    {
        $this->dependencyInjectionProxyClassBuilder = $dependencyInjectionProxyClassBuilder;
    }

    /**
     * @param Environment $environment
     * @return void
     */
    public function injectEnvironment(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Explicitly compile proxy classes
     *
     * The compile command triggers the proxy class compilation.
     * Although a compilation run is triggered automatically by Flow, there might
     * be cases in a production context where a manual compile run is needed.
     *
     * @Flow\Internal
     * @param boolean $force If set, classes will be compiled even though the cache says that everything is up to date.
     * @return void
     */
    public function compileCommand(bool $force = false)
    {
        /** @var VariableFrontend $objectConfigurationCache */
        $objectConfigurationCache = $this->cacheManager->getCache('Flow_Object_Configuration');
        if ($force === false) {
            if ($objectConfigurationCache->has('allCompiledCodeUpToDate')) {
                return;
            }
        }

        /** @var PhpFrontend $classesCache */
        $classesCache = $this->cacheManager->getCache('Flow_Object_Classes');
        $logger = $this->objectManager->get(PsrLoggerFactoryInterface::class)->get('systemLogger');

        $this->proxyClassCompiler->injectClassesCache($classesCache);

        $this->aopProxyClassBuilder->injectObjectConfigurationCache($objectConfigurationCache);
        $this->aopProxyClassBuilder->injectLogger($logger);
        $this->dependencyInjectionProxyClassBuilder->injectLogger($logger);
        $this->aopProxyClassBuilder->build();
        $this->dependencyInjectionProxyClassBuilder->build();

        $classCount = $this->proxyClassCompiler->compile();

        $dataTemporaryPath = $this->environment->getPathToTemporaryDirectory();
        Files::createDirectoryRecursively($dataTemporaryPath);
        file_put_contents($dataTemporaryPath . 'AvailableProxyClasses.php', $this->proxyClassCompiler->getStoredProxyClassMap());

        $objectConfigurationCache->set('allCompiledCodeUpToDate', true);

        $classesCacheBackend = $classesCache->getBackend();
        if ($this->bootstrap->getContext()->isProduction() && $classesCacheBackend instanceof FreezableBackendInterface) {
            /** @var FreezableBackendInterface $backend */
            $backend = $classesCache->getBackend();
            $backend->freeze();
        }

        $this->emitFinishedCompilationRun($classCount);
    }

    /**
     * Adjust file permissions for CLI and web server access
     *
     * This command adjusts the file permissions of the whole Flow application to
     * the given command line user and webserver user / group.
     *
     * @param string $commandlineUser User name of the command line user, for example "john"
     * @param string $webserverUser User name of the webserver, for example "www-data"
     * @param string $webserverGroup Group name of the webserver, for example "www-data"
     * @return void
     */
    public function setFilePermissionsCommand(string $commandlineUser, string $webserverUser, string $webserverGroup)
    {
        // This command will never be really called. It rather acts as a stub for rendering the
        // documentation for this command. In reality, the "flow" command line script will already
        // check if this command is supposed to be called and invoke the setfilepermissions script
        // directly.
    }

    /**
     * Migrate source files as needed
     *
     * This will apply pending code migrations defined in packages to the
     * specified package.
     *
     * For every migration that has been run, it will create a commit in
     * the package. This allows for easy inspection, rollback and use of
     * the fixed code.
     * If the affected package contains local changes or is not part of
     * a git repository, the migration will be skipped. With the --force
     * flag this behavior can be changed, but changes will only be committed
     * if the working copy was clean before applying the migration.
     *
     * @param string $package The key of the package to migrate
     * @param boolean $status Show the migration status, do not run migrations
     * @param string $packagesPath If set, use the given path as base when looking for packages
     * @param string $version If set, execute only the migration with the given version (e.g. "20150119114100")
     * @param boolean $verbose If set, notes and skipped migrations will be rendered
     * @param boolean $force By default packages that are not under version control or contain local changes are skipped. With this flag set changes are applied anyways (changes are not committed if there are local changes though)
     * @return void
     * @see neos.flow:doctrine:migrate
     */
    public function migrateCommand(string $package, bool $status = false, string $packagesPath = null, string $version = null, bool $verbose = false, bool $force = false)
    {
        // This command will never be really called. It rather acts as a stub for rendering the
        // documentation for this command. In reality, the "flow" command line script will already
        // check if this command is supposed to be called and invoke the migrate script
        // directly.
    }

    /**
     * Signals that the compile command was successfully finished.
     *
     * @param integer $classCount Number of compiled proxy classes
     * @return void
     * @Flow\Signal
     */
    protected function emitFinishedCompilationRun(int $classCount)
    {
        $this->signalSlotDispatcher->dispatch(__CLASS__, 'finishedCompilationRun', [$classCount]);
    }
}
