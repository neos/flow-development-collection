<?php
namespace TYPO3\Flow\Resource\Publishing;

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
use TYPO3\Flow\Log\SystemLoggerInterface;

/**
 * Notification Collector
 *
 * @Flow\Scope("singleton")
 */
class NotificationCollector
{
    /**
     * @var \SplObjectStorage
     */
    protected $notifications;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * Notification Collector constructor
     */
    public function __construct()
    {
        $this->notifications = new \SplObjectStorage();
    }

    /**
     * @param string $message The message to log
     * @param integer $severity An integer value, one of the LOG_* constants
     */
    public function append($message, $severity = LOG_INFO)
    {
        $this->notifications->attach(new Notification($message, $severity));
    }

    /**
     * @return boolean
     */
    public function hasNotification()
    {
        return $this->notifications->count() > 0;
    }

    /**
     * @param callable $callback a callback function to process every notification
     * @return \Generator
     */
    public function flush(callable $callback = null)
    {
        foreach ($this->notifications as $notification) {
            /** @var Notification $notification */
            $this->notifications->detach($notification);
            $this->systemLogger->log('ResourcePublishingNotification: ' . $notification->getMessage(), $notification->getSeverity());
            if ($callback !== null) {
                $callback($notification);
            }
        }
    }

    /**
     * Flush all notification during the object lifecycle
     */
    public function __destruct()
    {
        $this->flush();
    }
}
