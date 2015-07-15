<?php
namespace TYPO3\Flow\Utility\Lock;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Core\Bootstrap;

/**
 * A general lock class.
 *
 * @Flow\Scope("prototype")
 * @api
 */
class Lock {

	/**
	 * @var string
	 */
	protected static $lockStrategyClassName;

	/**
	 * @var \TYPO3\Flow\Utility\Lock\LockStrategyInterface
	 */
	protected $lockStrategy;

	/**
	 * @var string
	 */
	protected $subject;

	/**
	 * @var boolean
	 */
	protected $exclusiveLock = TRUE;

	/**
	 * @param string $subject
	 * @param boolean $exclusiveLock TRUE to, acquire an exclusive (write) lock, FALSE for a shared (read) lock. An exclusive lock ist the default.
	 */
	public function __construct($subject, $exclusiveLock = TRUE) {
		if (self::$lockStrategyClassName === NULL) {
			if (Bootstrap::$staticObjectManager === NULL || !Bootstrap::$staticObjectManager->isRegistered('TYPO3\Flow\Configuration\ConfigurationManager')) {
				return;
			}
			$configurationManager = Bootstrap::$staticObjectManager->get('TYPO3\Flow\Configuration\ConfigurationManager');
			$settings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');
			self::$lockStrategyClassName = $settings['utility']['lockStrategyClassName'];
		}
		$this->lockStrategy = new self::$lockStrategyClassName();
		$this->lockStrategy->acquire($subject, $exclusiveLock);
	}

	/**
	 * @return \TYPO3\Flow\Utility\Lock\LockStrategyInterface
	 */
	public function getLockStrategy() {
		return $this->lockStrategy;
	}

	/**
	 * Releases the lock
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	public function release() {
		if ($this->lockStrategy instanceof LockStrategyInterface) {
			return $this->lockStrategy->release();
		}
		return TRUE;
	}

	/**
	 * Destructor, releases the lock
	 * @return void
	 */
	public function __destruct() {
		$this->release();
	}
}
