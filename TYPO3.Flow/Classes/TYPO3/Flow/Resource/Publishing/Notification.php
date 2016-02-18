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

/**
 * Notification
 */
class Notification
{
    /**
     * @var string
     */
    protected $message;

    /**
     * @var integer
     */
    protected $severity;

    /**
     * @var array
     */
    protected $severityLabels = [
        LOG_EMERG => 'EMERGENCY',
        LOG_ALERT => 'ALERT',
        LOG_CRIT => 'CRITICAL',
        LOG_ERR => 'ERROR',
        LOG_WARNING => 'WARNING',
        LOG_NOTICE => 'NOTICE',
        LOG_INFO => 'INFO',
        LOG_DEBUG => 'DEBUG',
    ];

    /**
     * @param string $message The message to log
     * @param integer $severity An integer value, one of the LOG_* constants
     */
    public function __construct($message, $severity = LOG_INFO)
    {
        $this->message = $message;
        $this->severity = $severity;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return integer
     */
    public function getSeverity()
    {
        return $this->severity;
    }

    /**
     * @return string
     */
    public function getSeverityLabel()
    {
        return isset($this->severityLabels[$this->severity]) ? $this->severityLabels[$this->severity] : 'UNKNOW';
    }
}
