<?php
namespace TYPO3\Flow\Core;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

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
     * @var string
     */
    protected $lockPathAndFilename;

    /**
     * @var resource
     */
    protected $lockResource;

    /**
     * Builds the manager
     */
    public function __construct()
    {
        $this->lockPathAndFilename = FLOW_PATH_TEMPORARY . md5(FLOW_PATH_ROOT) . '_Flow.lock';
        if (file_exists($this->lockPathAndFilename) && filemtime($this->lockPathAndFilename) < (time() - self::LOCKFILE_MAXIMUM_AGE)) {
            @unlink($this->lockPathAndFilename);
        }
    }

    /**
     * Tells if the site is currently locked
     *
     * @return boolean
     * @api
     */
    public function isSiteLocked()
    {
        return file_exists($this->lockPathAndFilename);
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
            unlink($this->lockPathAndFilename);
            flock($this->lockResource, LOCK_UN);
            fclose($this->lockResource);
        }
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
            $expiresIn = abs((time() - self::LOCKFILE_MAXIMUM_AGE - filemtime($this->lockPathAndFilename)));
            echo 'Site is currently locked, exiting.' . PHP_EOL . 'The current lock will expire after ' . $expiresIn . ' seconds.' . PHP_EOL;
        }
        exit(1);
    }
}
