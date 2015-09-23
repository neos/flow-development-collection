<?php
namespace TYPO3\Flow\Log;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * The logger factory used to create logger instances.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class LoggerFactory
{
    /**
     * Factory method which creates the specified logger along with the specified backend(s).
     *
     * @param string $identifier An identifier for the logger
     * @param string $loggerObjectName Object name of the log frontend
     * @param mixed $backendObjectNames Object name (or array of object names) of the log backend(s)
     * @param array $backendOptions (optional) Array of backend options. If more than one backend is specified, this is an array of array.
     * @return \TYPO3\Flow\Log\LoggerInterface The created logger frontend
     * @api
     */
    public static function create($identifier, $loggerObjectName, $backendObjectNames, array $backendOptions = array())
    {
        $logger = new $loggerObjectName;

        if (is_array($backendObjectNames)) {
            foreach ($backendObjectNames as $i => $backendObjectName) {
                if (isset($backendOptions[$i])) {
                    $backend = new $backendObjectName($backendOptions[$i]);
                    $logger->addBackend($backend);
                }
            }
        } else {
            $backend = new $backendObjectNames($backendOptions);
            $logger->addBackend($backend);
        }
        return $logger;
    }
}
