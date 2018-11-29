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

/**
 * Command controller for managing sessions
 *
 * NOTE: This command controller will run in compile time (as defined in the package bootstrap)
 *
 * @Flow\Scope("singleton")
 */
class SessionCommandController extends CommandController
{
    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @param CacheManager $cacheManager
     * @return void
     */
    public function injectCacheManager(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Destroys all sessions.
     * This special command is needed, because sessions are kept in persistent storage and are not flushed
     * with other caches by default.
     *
     * This is functionally equivalent to
     * `./flow flow:cache:flushOne Flow_Session_Storage && ./flow flow:cache:flushOne Flow_Session_MetaData`
     *
     * @return void
     * @since 5.2
     */
    public function destroyAllCommand()
    {
        $this->cacheManager->getCache('Flow_Session_Storage')->flush();
        $this->cacheManager->getCache('Flow_Session_MetaData')->flush();
        $this->outputLine('Destroyed all sessions.');
        $this->sendAndExit(0);
    }
}
