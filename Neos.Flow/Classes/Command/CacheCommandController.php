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
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\Response;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Core\LockManager;
use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\Utility\Environment;

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
     * @var PackageManagerInterface
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
     * @param PackageManagerInterface $packageManager
     * @return void
     */
    public function injectPackageManager(PackageManagerInterface $packageManager)
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
     * registered with Flow's Cache Manager. It also removes any session data.
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
    public function flushCommand($force = false)
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
        foreach (array_keys($this->packageManager->getActivePackages()) as $packageKey) {
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
    public function flushOneCommand($identifier)
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
     * Call system function
     *
     * @Flow\Internal
     * @param integer $address
     * @return void
     */
    public function sysCommand($address)
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
