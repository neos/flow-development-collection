<?php
namespace TYPO3\Flow\Persistence\Doctrine\Logging;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */


/**
 * A SQL logger that logs to a Flow logger.
 *
 */
class SqlLogger implements \Doctrine\DBAL\Logging\SQLLogger
{
    /**
     * @var \TYPO3\Flow\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Logs a SQL statement to the system logger (DEBUG priority).
     *
     * @param string $sql The SQL to be executed
     * @param array $params The SQL parameters
     * @param array $types The SQL parameter types.
     * @return void
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        // this is a safeguard for when no logger might be available...
        if ($this->logger !== null) {
            $this->logger->log($sql, LOG_DEBUG, array('params' => $params, 'types' => $types));
        }
    }

    /**
     * @return void
     */
    public function stopQuery()
    {
    }
}
