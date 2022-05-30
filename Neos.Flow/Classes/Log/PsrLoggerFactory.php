<?php
declare(strict_types=1);

namespace Neos\Flow\Log;

use Neos\Flow\Log\Backend\BackendInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Psr\Logger;

/**
 * This actually creates a logger from the Neos.Log package.
 * It is no dependency of Neos.Flow but a suggestion.
 * So IF you use the default logging Neos.Log needs to be installed.
 *
 * @Flow\Proxy(false)
 * @api
 */
class PsrLoggerFactory implements PsrLoggerFactoryInterface
{
    /**
     * @var \Psr\Log\LoggerInterface[]
     */
    protected $instances = [];

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * PsrLoggerFactory constructor.
     *
     * @param array $configuration
     */
    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration;
    }

    /**
     * Get the logger configured with given identifier.
     * This implementation treats the logger as singleton.
     *
     * @param string $identifier
     * @return \Psr\Log\LoggerInterface
     * @throws \Exception
     */
    public function get(string $identifier): \Psr\Log\LoggerInterface
    {
        if (isset($this->instances[$identifier])) {
            return $this->instances[$identifier];
        }

        if (!isset($this->configuration[$identifier])) {
            throw new \InvalidArgumentException(sprintf('The given log identifier "%s" was not configured for the "%s" factory.', htmlspecialchars($identifier), self::class), 1515355505545);
        }

        if (!class_exists(Logger::class)) {
            throw new \Exception('To use the default logging you have to have the "neos/flow-log" package installed. It seems you miss it, so install it via "composer require neos/flow-log".', 1515437383589);
        }

        $backends = $this->instantiateBackends($this->configuration[$identifier]);

        $logger = new Logger($backends);
        $this->instances[$identifier] = $logger;
        return $logger;
    }

    /**
     * Create a new instance of this PsrLoggerFactory
     *
     * @param array $configuration
     * @return static
     * @api
     */
    public static function create(array $configuration): PsrLoggerFactory
    {
        return new self($configuration);
    }

    /**
     * Instantiate all configured backends
     *
     * @param array $configuration
     * @return BackendInterface[]
     * @throws \Exception
     */
    protected function instantiateBackends(array $configuration): array
    {
        $backends = [];
        foreach ($configuration as $backendConfiguration) {
            $class = $backendConfiguration['class'] ?? '';
            $options = $backendConfiguration['options'] ?? [];
            $backends[] = $this->instantiateBackend($class, $options);
        }

        return $backends;
    }

    /**
     * Instantiate a backend based on configuration.
     *
     * @param string $class
     * @param array $options
     * @return BackendInterface
     * @throws \Exception
     */
    protected function instantiateBackend(string $class, array $options = []): BackendInterface
    {
        if (!class_exists($class)) {
            throw new \Exception(sprintf('The log backend class "%s" does not exist', htmlspecialchars($class)), 1559318313);
        }

        $backend = new $class($options);
        if (!($backend instanceof BackendInterface)) {
            throw new \Exception(sprintf('The log backend class "%s" does not implement the BackendInterface', htmlspecialchars($class)), 1515355501);
        }

        return $backend;
    }
}
