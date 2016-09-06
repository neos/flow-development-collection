<?php
namespace TYPO3\Flow\Utility\Lock;

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
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Core\Bootstrap;

/**
 * A general lock class.
 *
 * @Flow\Scope("prototype")
 * @api
 */
class Lock
{
    /**
     * @var string
     */
    protected static $lockStrategyClassName;

    /**
     * @var LockStrategyInterface
     */
    protected $lockStrategy;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var boolean
     */
    protected $exclusiveLock = true;

    /**
     * @param string $subject
     * @param boolean $exclusiveLock TRUE to, acquire an exclusive (write) lock, FALSE for a shared (read) lock. An exclusive lock ist the default.
     */
    public function __construct($subject, $exclusiveLock = true)
    {
        if (self::$lockStrategyClassName === null) {
            if (Bootstrap::$staticObjectManager === null || !Bootstrap::$staticObjectManager->isRegistered(ConfigurationManager::class)) {
                return;
            }
            $configurationManager = Bootstrap::$staticObjectManager->get(ConfigurationManager::class);
            $settings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');
            self::$lockStrategyClassName = $settings['utility']['lockStrategyClassName'];
        }
        $this->lockStrategy = new self::$lockStrategyClassName();
        $this->lockStrategy->acquire($subject, $exclusiveLock);
    }

    /**
     * @return LockStrategyInterface
     */
    public function getLockStrategy()
    {
        return $this->lockStrategy;
    }

    /**
     * Releases the lock
     * @return boolean TRUE on success, FALSE otherwise
     */
    public function release()
    {
        if ($this->lockStrategy instanceof LockStrategyInterface) {
            return $this->lockStrategy->release();
        }
        return true;
    }

    /**
     * Destructor, releases the lock
     * @return void
     */
    public function __destruct()
    {
        $this->release();
    }
}
