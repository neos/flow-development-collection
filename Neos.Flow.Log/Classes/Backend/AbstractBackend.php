<?php
namespace Neos\Flow\Log\Backend;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Log\PlainTextFormatter;

/**
 * An abstract Log backend
 *
 * @api
 */
abstract class AbstractBackend implements BackendInterface
{
    /**
     * One of the LOG_* constants. Anything below that will be filtered out.
     * @var integer
     */
    protected $severityThreshold = LOG_INFO;

    /**
     * Flag telling if the IP address of the current client (if available) should be logged.
     * @var boolean
     */
    protected $logIpAddress = false;

    /**
     * Constructs this log backend
     *
     * @param mixed $options Configuration options - depends on the actual backend
     * @api
     */
    public function __construct($options = [])
    {
        if (is_array($options) || $options instanceof \ArrayAccess) {
            foreach ($options as $optionKey => $optionValue) {
                $methodName = 'set' . ucfirst($optionKey);
                if (method_exists($this, $methodName)) {
                    $this->$methodName($optionValue);
                }
            }
        }
    }

    /**
     * The maximum severity to log, anything less severe will not be logged.
     *
     * @param integer $severityThreshold One of the LOG_* constants
     * @return void
     * @api
     */
    public function setSeverityThreshold($severityThreshold)
    {
        $this->severityThreshold = $severityThreshold;
    }

    /**
     * Enables or disables logging of IP addresses.
     *
     * @param boolean $logIpAddress Set to true to enable logging of IP address, or false to disable
     * @return void
     */
    public function setLogIpAddress($logIpAddress)
    {
        $this->logIpAddress = $logIpAddress;
    }

    /**
     * Returns a suitable form of a variable (be it a string, array, object ...) for logfile output
     *
     * @param mixed $var The variable
     * @param integer $spaces Number of spaces to add before a line
     * @return string text output
     * @deprecated Use the PlainTextFormatter directly
     * @see PlainTextFormatter
     */
    protected function getFormattedVarDump($var, $spaces = 4)
    {
        return (new PlainTextFormatter($var))->format($spaces);
    }
}
