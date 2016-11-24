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
     * @param boolean $logIpAddress Set to TRUE to enable logging of IP address, or FALSE to disable
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
     */
    protected function getFormattedVarDump($var, $spaces = 4)
    {
        if ($spaces > 100) {
            return null;
        }
        $output = '';
        if (is_array($var)) {
            foreach ($var as $k => $v) {
                if (is_array($v)) {
                    $output .= str_repeat(' ', $spaces) . $k . ' => array (' . PHP_EOL . $this->getFormattedVarDump($v, $spaces + 3) . str_repeat(' ', $spaces) . ')' . PHP_EOL;
                } else {
                    if (is_object($v)) {
                        $output .= str_repeat(' ', $spaces) . $k . ' => object: ' . get_class($v) . PHP_EOL;
                    } else {
                        $output .= str_repeat(' ', $spaces) . $k . ' => ' . ($v === null ? '␀' : $v) . PHP_EOL;
                    }
                }
            }
        } else {
            if (is_object($var)) {
                $output .= str_repeat(' ', $spaces) . ' [ OBJECT: ' . strtoupper(get_class($var)) . ' ]:' . PHP_EOL;
                if (is_array(get_object_vars($var))) {
                    foreach (get_object_vars($var) as $objVarName => $objVarValue) {
                        if (is_array($objVarValue) || is_object($objVarValue)) {
                            $output .= str_repeat(' ', $spaces) . $objVarName . ' => ' . PHP_EOL;
                            $output .= $this->getFormattedVarDump($objVarValue, $spaces + 3);
                        } else {
                            $output .= str_repeat(' ', $spaces) . $objVarName . ' => ' . ($objVarValue === null ? '␀' : $objVarValue) . PHP_EOL;
                        }
                    }
                }
                $output .= PHP_EOL;
            } else {
                $output .= str_repeat(' ', $spaces) . '=> ' . ($var === null ? '␀' : $var) . PHP_EOL;
            }
        }
        return $output;
    }
}
