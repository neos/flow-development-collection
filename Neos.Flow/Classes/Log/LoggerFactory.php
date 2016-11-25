<?php
namespace Neos\Flow\Log;

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
     * @return \Neos\Flow\Log\LoggerInterface The created logger frontend
     * @api
     */
    public function create($identifier, $loggerObjectName, $backendObjectNames, array $backendOptions = [])
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
