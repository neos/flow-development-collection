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

use Neos\Cache\Backend\WithSetupInterface;
use Neos\Cache\Backend\WithStatusInterface;
use Neos\Cache\Exception\NoSuchCacheException;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Notice;
use Neos\Error\Messages\Result;
use Neos\Error\Messages\Warning;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\Response;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Core\LockManager;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Utility\Environment;
use Neos\Utility\TypeHandling;

/**
 * Command controller for managing caches
 *
 * NOTE: This command controller will run in compile time (as defined in the package bootstrap)
 *
 * @Flow\Scope("singleton")
 */
class CacheCommandController extends CommandController
{
    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var LockManager
     */
    protected $lockManager;

    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @param CacheManager $cacheManager
     * @return void
     */
    public function injectCacheManager(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * @param LockManager $lockManager
     * @return void
     */
    public function injectLockManager(LockManager $lockManager)
    {
        $this->lockManager = $lockManager;
    }

    /**
     * @param PackageManager $packageManager
     * @return void
     */
    public function injectPackageManager(PackageManager $packageManager)
    {
        $this->packageManager =  $packageManager;
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
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
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
     * Flush all caches
     *
     * The flush command flushes all caches (including code caches) which have been
     * registered with Flow's Cache Manager. It will NOT remove any session data, unless
     * you specifically configure the session caches to not be persistent.
     *
     * If fatal errors caused by a package prevent the compile time bootstrap
     * from running, the removal of any temporary data can be forced by specifying
     * the option <b>--force</b>.
     *
     * This command does not remove the precompiled data provided by frozen
     * packages unless the <b>--force</b> option is used.
     *
     * @param boolean $force Force flushing of any temporary data
     * @return void
     * @see neos.flow:cache:warmup
     * @see neos.flow:package:freeze
     * @see neos.flow:package:refreeze
     */
    public function flushCommand(bool $force = false)
    {

        // Internal note: the $force option is evaluated early in the Flow
        // bootstrap in order to reliably flush the temporary data before any
        // other code can cause fatal errors.

        $this->cacheManager->flushCaches();

        $this->outputLine('Flushed all caches for "' . $this->bootstrap->getContext() . '" context.');
        if ($this->lockManager->isSiteLocked()) {
            $this->lockManager->unlockSite();
        }

        $frozenPackages = [];
        foreach (array_keys($this->packageManager->getAvailablePackages()) as $packageKey) {
            if ($this->packageManager->isPackageFrozen($packageKey)) {
                $frozenPackages[] = $packageKey;
            }
        }
        if ($frozenPackages !== []) {
            $this->outputFormatted(PHP_EOL . 'Please note that the following package' . (count($frozenPackages) === 1 ? ' is' : 's are') . ' currently frozen: ' . PHP_EOL);
            $this->outputFormatted(implode(PHP_EOL, $frozenPackages) . PHP_EOL, [], 2);

            $message = 'As code and configuration changes in these packages are not detected, the application may respond ';
            $message .= 'unexpectedly if modifications were done anyway or the remaining code relies on these changes.' . PHP_EOL . PHP_EOL;
            $message .= 'You may call <b>package:refreeze all</b> in order to refresh frozen packages or use the <b>--force</b> ';
            $message .= 'option of this <b>cache:flush</b> command to flush caches if Flow becomes unresponsive.' . PHP_EOL;
            $this->outputFormatted($message, [$frozenPackages]);
        }

        $this->sendAndExit(0);
    }

    /**
     * Flushes a particular cache by its identifier
     *
     * Given a cache identifier, this flushes just that one cache. To find
     * the cache identifiers, you can use the configuration:show command with
     * the type set to "Caches".
     *
     * Note that this does not have a force-flush option since it's not
     * meant to remove temporary code data, resulting into a broken state if
     * code files lack.
     *
     * @param string $identifier Cache identifier to flush cache for
     * @return void
     * @see neos.flow:cache:flush
     * @see neos.flow:configuration:show
     */
    public function flushOneCommand(string $identifier)
    {
        if (!$this->cacheManager->hasCache($identifier)) {
            $this->outputLine('The cache "%s" does not exist.', [$identifier]);

            $cacheConfigurations = $this->cacheManager->getCacheConfigurations();
            $shortestDistance = -1;
            foreach (array_keys($cacheConfigurations) as $existingIdentifier) {
                $distance = levenshtein($existingIdentifier, $identifier);
                if ($distance <= $shortestDistance || $shortestDistance < 0) {
                    $shortestDistance = $distance;
                    $closestIdentifier = $existingIdentifier;
                }
            }

            if (isset($closestIdentifier)) {
                $this->outputLine('Did you mean "%s"?', [$closestIdentifier]);
            }

            $this->quit(1);
        }
        $this->cacheManager->getCache($identifier)->flush();
        $this->outputLine('Flushed "%s" cache for "%s" context.', [$identifier, $this->bootstrap->getContext()]);
        $this->sendAndExit(0);
    }

    /**
     * Warm up caches
     *
     * The warm up caches command initializes and fills – as far as possible – all
     * registered caches to get a snappier response on the first following request.
     * Apart from caches, other parts of the application may hook into this command
     * and execute tasks which take further steps for preparing the app for the big
     * rush.
     *
     * @return void
     * @see neos.flow:cache:flush
     */
    public function warmupCommand()
    {
        $this->emitWarmupCaches();
        $this->outputLine('Warmed up caches.');
    }

    /**
     * List all configured caches and their status if available
     *
     * This command will exit with a code 1 if at least one cache status contains errors or warnings
     * This allows the command to be easily integrated in CI setups (the --quiet flag can be used to reduce verbosity)
     *
     * @param bool $quiet If set, this command only outputs errors & warnings
     * @return void
     * @see neos.flow:cache:show
     * @throws NoSuchCacheException|StopActionException
     */
    public function listCommand(bool $quiet = false)
    {
        $cacheConfigurations = $this->cacheManager->getCacheConfigurations();
        $defaultConfiguration = $cacheConfigurations['Default'];
        unset($cacheConfigurations['Default']);
        ksort($cacheConfigurations);

        $headers = ['Cache', 'Backend', 'Status'];

        $erroneousCaches = [];
        $rows = [];
        foreach ($cacheConfigurations as $identifier => $configuration) {
            $cache = $this->cacheManager->getCache($identifier);
            if (isset($configuration['persistent']) && $configuration['persistent'] === true) {
                $identifier = $identifier . ' <comment>*</comment>';
            }
            $backendClassName = isset($configuration['backend']) ? '<b>' . $configuration['backend'] . '</b>' : $defaultConfiguration['backend'];
            $cacheBackend = $cache->getBackend();
            if (!$cacheBackend instanceof WithStatusInterface) {
                $status = '?';
            } else {
                $statusResult = $cacheBackend->getStatus();
                if ($statusResult->hasErrors()) {
                    $erroneousCaches[] = $identifier;
                    $status = '<error>ERROR</error>';
                } elseif ($statusResult->hasWarnings()) {
                    $erroneousCaches[] = $identifier;
                    $status = '<comment>WARNING</comment>';
                } else {
                    $status = '<success>OK</success>';
                }
            }
            $row = [$identifier, $backendClassName, $status];
            $rows[] = $row;
        }
        if (!$quiet) {
            $this->output->outputTable($rows, $headers);

            $this->outputLine('<comment>*</comment> = Persistent Cache');
            $this->outputLine('<b>Bold = Custom</b>, Thin = Default');
            $this->outputLine();
        }
        if ($erroneousCaches !== []) {
            $this->outputLine('<error>The following caches contain errors or warnings: %s</error>', [implode(', ', $erroneousCaches)]);
            $quiet || $this->outputLine('Use the <em>neos.flow:cache:show</em> Command for more information');
            $this->quit(1);
            return;
        }
    }

    /**
     * Display details of a cache including a detailed status if available
     *
     * @param string $cacheIdentifier identifier of the cache (for example "Flow_Core")
     * @return void
     * @see neos.flow:cache:list
     * @throws StopActionException
     */
    public function showCommand(string $cacheIdentifier)
    {
        try {
            $cache = $this->cacheManager->getCache($cacheIdentifier);
        } catch (NoSuchCacheException $exception) {
            $this->outputLine('<error>A Cache with id "%s" is not configured.</error>', [$cacheIdentifier]);
            $this->outputLine('Use the <i>neos.flow:cache:list</i> command to get a list of all configured Caches.');
            $this->quit(1);
            return;
        }
        $cacheConfigurations = $this->cacheManager->getCacheConfigurations();
        $defaultConfiguration = $cacheConfigurations['Default'];
        $cacheConfiguration = $cacheConfigurations[$cache->getIdentifier()];
        $cacheBackend = $cache->getBackend();
        $this->outputLine('<b>Identifier</b>: %s', [$cache->getIdentifier()]);
        $this->outputLine('<b>Frontend</b>: %s', [TypeHandling::getTypeForValue($cache)]);
        $this->outputLine('<b>Backend</b>: %s', [TypeHandling::getTypeForValue($cacheBackend)]);
        $options = $cacheConfiguration['backendOptions'] ?? $defaultConfiguration['backendOptions'];
        $this->outputLine('<b>Backend Options</b>:');
        $this->outputLine(json_encode($options, JSON_PRETTY_PRINT));

        if ($cacheBackend instanceof WithStatusInterface) {
            $this->outputLine();
            $this->outputLine('<b>Status:</b>');
            $this->outputLine('=======');
            $cacheStatus = $cacheBackend->getStatus();
            $this->renderResult($cacheStatus);

            if ($cacheStatus->hasErrors() || $cacheStatus->hasWarnings()) {
                $this->quit(1);
                return;
            }
        }
    }

    /**
     * Setup the given Cache if possible
     *
     * Invokes the setup() method on the configured CacheBackend (if it implements the WithSetupInterface)
     * which should setup and validate the backend (i.e. create required database tables, directories, ...)
     *
     * @param string $cacheIdentifier
     * @return void
     * @see neos.flow:cache:list
     * @see neos.flow:cache:setupall
     * @throws StopActionException
     */
    public function setupCommand(string $cacheIdentifier)
    {
        try {
            $cache = $this->cacheManager->getCache($cacheIdentifier);
        } catch (NoSuchCacheException $exception) {
            $this->outputLine('<error>A Cache with id "%s" is not configured.</error>', [$cacheIdentifier]);
            $this->outputLine('Use the <i>neos.flow:cache:list</i> command to get a list of all configured Caches.');
            $this->quit(1);
            return;
        }
        $cacheBackend = $cache->getBackend();
        if (!$cacheBackend instanceof WithSetupInterface) {
            $this->outputLine('<error>The Cache "%s" is configured to use the backend "%s" but this does not implement the WithSetupInterface.</error>', [$cacheIdentifier, TypeHandling::getTypeForValue($cacheBackend)]);
            $this->quit(1);
            return;
        }
        $this->outputLine('Setting up backend <b>%s</b> for cache "%s"', [TypeHandling::getTypeForValue($cacheBackend), $cache->getIdentifier()]);
        $setupResult = $cacheBackend->setup();
        $this->renderResult($setupResult);
        if ($setupResult->hasErrors() || $setupResult->hasWarnings()) {
            $this->quit(1);
            return;
        }
    }

    /**
     * Setup all Caches
     *
     * Invokes the setup() method on all configured CacheBackend that implement the WithSetupInterface interface
     * which should setup and validate the backend (i.e. create required database tables, directories, ...)
     *
     * This command will exit with a code 1 if at least one cache setup failed
     * This allows the command to be easily integrated in CI setups (the --quiet flag can be used to reduce verbosity)
     *
     * @param bool $quiet If set, this command only outputs errors & warnings
     * @return void
     * @see neos.flow:cache:setup
     * @throws NoSuchCacheException|StopActionException
     */
    public function setupAllCommand(bool $quiet = false)
    {
        $cacheConfigurations = $this->cacheManager->getCacheConfigurations();
        unset($cacheConfigurations['Default']);
        $hasErrorsOrWarnings = false;
        foreach (array_keys($cacheConfigurations) as $cacheIdentifier) {
            $cache = $this->cacheManager->getCache($cacheIdentifier);
            $cacheBackend = $cache->getBackend();
            $quiet || $this->outputLine('<b>%s:</b>', [$cache->getIdentifier()]);
            if (!$cacheBackend instanceof WithSetupInterface) {
                $quiet || $this->outputLine('Skipped, because backend "%s" does not implement the WithSetupInterface', [TypeHandling::getTypeForValue($cacheBackend)]);
                continue;
            }
            $result = $cacheBackend->setup();
            $hasErrorsOrWarnings = $hasErrorsOrWarnings || $result->hasErrors() || $result->hasWarnings();
            if (!$quiet || $result->hasErrors()) {
                $this->renderResult($result);
            }
        }
        if ($hasErrorsOrWarnings) {
            $this->quit(1);
            return;
        }
    }

    /**
     * Cache Garbage Collection
     *
     * Runs the Garbage Collection (collectGarbage) method on all registered caches.
     *
     * Though the method is defined in the BackendInterface, the implementation
     * can differ and might not remove any data, depending on possibilities of
     * the backend.
     *
     * @param string $cacheIdentifier If set, this command only applies to the given cache
     * @return void
     * @throws NoSuchCacheException
     */
    public function collectGarbageCommand(string $cacheIdentifier = null): void
    {
        if ($cacheIdentifier !== null) {
            $cache = $this->cacheManager->getCache($cacheIdentifier);
            $cache->collectGarbage();

            $this->outputLine('<success>Garbage Collection for cache "%s" completed</success>', [$cacheIdentifier]);
        } else {
            $cacheConfigurations = $this->cacheManager->getCacheConfigurations();
            unset($cacheConfigurations['Default']);
            ksort($cacheConfigurations);

            foreach ($cacheConfigurations as $identifier => $configuration) {
                $this->outputLine('Garbage Collection for cache "%s"', [$identifier]);
                $cache = $this->cacheManager->getCache($identifier);
                $cache->collectGarbage();
                $this->outputLine('<success>Completed</success>');
            }
        }
    }

    /**
     * Call system function
     *
     * @Flow\Internal
     * @param integer $address
     * @return void
     */
    public function sysCommand(int $address)
    {
        if ($address === 64738) {
            $this->cacheManager->flushCaches();
            $content = 'G1syShtbMkobWzE7MzdtG1sxOzQ0bSAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAbWzBtChtbMTszN20bWzE7NDRtICAgICAgKioqKiBDT01NT0RPUkUgNjQgQkFTSUMgVjIgKioqKiAgICAgIBtbMG0KG1sxOzM3bRtbMTs0NG0gIDY0SyBSQU0gU1lTVEVNICAzODkxMSBCQVNJQyBCWVRFUyBGUkVFICAgG1swbQobWzE7MzdtG1sxOzQ0bSBSRUFEWS4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAbWzBtChtbMTszN20bWzE7NDRtIEZMVVNIIENBQ0hFICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIBtbMG0KG1sxOzM3bRtbMTs0NG0gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgG1swbQobWzE7MzdtG1sxOzQ0bSBPSywgRkxVU0hFRCBBTEwgQ0FDSEVTLiAgICAgICAgICAgICAgICAgICAbWzBtChtbMTszN20bWzE7NDRtIFJFQURZLiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIBtbMG0KG1sxOzM3bRtbMTs0NG0gG1sxOzQ3bSAbWzE7NDRtICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAbWzBtChtbMTszN20bWzE7NDRtICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIBtbMG0KG1sxOzM3bRtbMTs0NG0gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgG1swbQobWzE7MzdtG1sxOzQ0bSAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAbWzBtChtbMTszN20bWzE7NDRtICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIBtbMG0KG1sxOzM3bRtbMTs0NG0gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgG1swbQobWzE7MzdtG1sxOzQ0bSAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAbWzBtChtbMTszN20bWzE7NDRtICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIBtbMG0KG1sxOzM3bRtbMTs0NG0gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgG1swbQoK';
            $this->response->setOutputFormat(Response::OUTPUTFORMAT_RAW);
            $this->response->appendContent(base64_decode($content));
            if ($this->lockManager->isSiteLocked()) {
                $this->lockManager->unlockSite();
            }
            $this->sendAndExit(0);
        }
    }

    /**
     * Outputs the given Result object in a human-readable way
     *
     * @param Result $result
     * @return void
     */
    private function renderResult(Result $result)
    {
        if ($result->hasNotices()) {
            /** @var Notice $notice */
            foreach ($result->getNotices() as $notice) {
                if ($notice->hasTitle()) {
                    $this->outputLine('<b>%s</b>: %s', [$notice->getTitle(), $notice->render()]);
                } else {
                    $this->outputLine($notice->render());
                }
            }
        }

        if ($result->hasErrors()) {
            /** @var Error $error */
            foreach ($result->getErrors() as $error) {
                $this->outputLine('<error>ERROR: %s</error>', [$error->render()]);
            }
        }
        if ($result->hasWarnings()) {
            /** @var Warning $warning */
            foreach ($result->getWarnings() as $warning) {
                if ($warning->hasTitle()) {
                    $this->outputLine('<b>%s</b>: <comment>%s</comment>', [$warning->getTitle(), $warning->render()]);
                } else {
                    $this->outputLine('<comment>%s</comment>', [$warning->render()]);
                }
            }
        }
        if (!$result->hasErrors() && !$result->hasWarnings()) {
            $this->outputLine('<success>OK</success>');
        }
    }

    /**
     * Signals that caches should be warmed up.
     *
     * Other application parts may subscribe to this signal and execute additional
     * tasks for preparing the application for the first request.
     *
     * @return void
     * @Flow\Signal
     */
    public function emitWarmupCaches()
    {
    }
}
