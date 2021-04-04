<?php
namespace Neos\Flow\Log;

/**
 * Interface for PSR-3 logger factories. Any adapter for
 * logging in Flow should bring such a factory.
 *
 * @package Neos\Flow\Log
 * @api
 */
interface PsrLoggerFactoryInterface
{
    /**
     * Create a PSR-3 logger based on the given configuration.
     * The correct configuration and format is dependent on the implementation.
     *
     * @param string $identifier A name for the logger. Factories MAY decide to treat a logger a singleton and return
     * @return \Psr\Log\LoggerInterface
     * @api
     */
    public function get(string $identifier);

    /**
     * Create an instance of this LoggerFactory, with a given configuration.
     * If your implementation needs additional dependencies you might need to wrap
     * creation with those dependencies internally. The LoggerFactory is created so
     * easy in the process that there are not many dependencies to be had anyway and
     * they would have to be injected manually anyway as there is no reflection or
     * autowiring at this point.
     * Reminder for super low level things like this, the Bootstrap class has
     * some static properties for you.
     *
     * @param array $configuration
     * @return static
     * @api
     */
    public static function create(array $configuration);
}
