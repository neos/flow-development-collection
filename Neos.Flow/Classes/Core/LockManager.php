<?php
namespace Neos\Flow\Core;

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

/**
 * The Lock Manager controls the master lock of the whole site which is mainly
 * used to regenerate code caches in peace.
 *
 * @Flow\Scope("singleton")
 */
class LockManager
{
    /**
     * @var integer
     */
    const LOCKFILE_MAXIMUM_AGE = 90;

    /**
     * This file contains the actual lock, set via \flock() in lockSiteOrExit()
     *
     * @var string
     */
    protected $lockPathAndFilename;

    /**
     * This file is used to track the status of the site lock: If it exists the site is considered locked.
     * See https://jira.neos.io/browse/FLOW-365 for some background information
     *
     * @var string
     */
    protected $lockFlagPathAndFilename;

    /**
     * @var resource
     */
    protected $lockResource;

    /**
     * Initializes the manager, removing expired locks
     */
    public function __construct()
    {
        $lockPath = $this->getLockPath();
        $this->lockPathAndFilename = $lockPath . md5(FLOW_PATH_ROOT) . '_Flow.lock';
        $this->lockFlagPathAndFilename = $lockPath . md5(FLOW_PATH_ROOT) . '_FlowIsLocked';
        $this->removeExpiredLock();
    }

    /**
     * Returns the absolute path to a directory that should contain the lock files
     *
     * @return string
     */
    protected function getLockPath()
    {
        return FLOW_PATH_TEMPORARY;
    }

    /**
     * @return void
     */
    protected function removeExpiredLock()
    {
        if (!file_exists($this->lockFlagPathAndFilename)) {
            return;
        }
        if (filemtime($this->lockFlagPathAndFilename) >= (time() - self::LOCKFILE_MAXIMUM_AGE)) {
            return;
        }
        @unlink($this->lockFlagPathAndFilename);
        @unlink($this->lockPathAndFilename);
    }

    /**
     * Tells if the site is currently locked
     *
     * @return boolean
     * @api
     */
    public function isSiteLocked()
    {
        return file_exists($this->lockFlagPathAndFilename);
    }

    /**
     * Exits if the site is currently locked
     *
     * @return void
     */
    public function exitIfSiteLocked()
    {
        if ($this->isSiteLocked() === true) {
            $this->doExit();
        }
    }

    /**
     * Locks the site for further requests.
     *
     * @return void
     * @api
     */
    public function lockSiteOrExit()
    {
        touch($this->lockFlagPathAndFilename);
        $this->lockResource = fopen($this->lockPathAndFilename, 'w+');
        if (!flock($this->lockResource, LOCK_EX | LOCK_NB)) {
            fclose($this->lockResource);
            $this->doExit();
        }
    }

    /**
     * Unlocks the site if this request has locked it.
     *
     * @return void
     * @api
     */
    public function unlockSite()
    {
        if (is_resource($this->lockResource)) {
            flock($this->lockResource, LOCK_UN);
            fclose($this->lockResource);
            unlink($this->lockPathAndFilename);
        }
        @unlink($this->lockFlagPathAndFilename);
    }

    /**
     * Exit and emit a message about the reason.
     *
     * @return void
     */
    protected function doExit()
    {
        if (FLOW_SAPITYPE === 'Web') {
            header('HTTP/1.1 503 Service Temporarily Unavailable');
            readfile(FLOW_PATH_FLOW . 'Resources/Private/Core/LockHoldingStackPage.html');
        } else {
            $expiresIn = abs((time() - self::LOCKFILE_MAXIMUM_AGE - filemtime($this->lockFlagPathAndFilename)));
            echo 'Site is currently locked, exiting.' . PHP_EOL . 'The current lock will expire after ' . $expiresIn . ' seconds.' . PHP_EOL;
        }
        exit(1);
    }
}
