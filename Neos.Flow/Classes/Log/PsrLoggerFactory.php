<?php
namespace Neos\Flow\Log;

use Neos\Flow\Log\Backend\BackendInterface;
use Neos\Flow\Annotations as Flow;

/**
 * This actually creates a logger from the Neos.Log package.
 * It is no dependency of Neos.Flow but a suggestion.
 * So IF you use the default logging Neos.Log needs to be installed.
 *
 * @Flow\Proxy(false)
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
    public function get(string $identifier)
    {
        if (isset($this->instances[$identifier])) {
            return $this->instances[$identifier];
        }

        if (!isset($this->configuration[$identifier])) {
            throw new \InvalidArgumentException(sprintf('The given log identifier "%s" was not configured for the "%s" factory.', htmlspecialchars($identifier), self::class), 1515355505545);
        }

        $backends = $this->instantiateBackends($this->configuration[$identifier]);

        if (!class_exists(PsrLogger::class)) {
            throw new \Exception('To use the default logging you have to have the "neos/flow-log" package installed. It seems you miss it, so install it via "composer require neos/flow-log".', 1515437383589);
        }

        $logger = new PsrLogger($backends);
        $this->instances[$identifier] = $logger;
    }

    /**
     * @param array $configuration
     * @return void|static
     */
    public static function create(array $configuration)
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
    protected function instantiateBackends(array $configuration)
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
    protected function instantiateBackend(string $class, array $options = [])
    {
        if (!is_a($class, BackendInterface::class)) {
            throw new \Exception(sprinf('The log backend class "%s" does not implement the BackendInterface', htmlspecialchars($class)), 1515355501615);
        }
        return $class($options);
    }
}
