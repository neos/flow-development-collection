<?php
namespace Neos\Cache;

use Neos\Cache\Backend\BackendInterface;
use Neos\Cache\Exception\InvalidBackendException;

trait BackendInstantiationTrait
{
    /**
     * @param string $backendObjectName
     * @param array $backendOptions
     * @param EnvironmentConfiguration $environmentConfiguration
     * @return BackendInterface
     * @throws InvalidBackendException
     */
    protected function instantiateBackend(string $backendObjectName, array $backendOptions, EnvironmentConfiguration $environmentConfiguration) : BackendInterface
    {
        $backend = new $backendObjectName($environmentConfiguration, $backendOptions);
        if (!$backend instanceof BackendInterface) {
            throw new InvalidBackendException('"' . $backendObjectName . '" is not a valid cache backend object.', 1216304302);
        }

        return $backend;
    }
}
