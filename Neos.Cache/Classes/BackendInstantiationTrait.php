<?php

namespace Neos\Cache;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\BackendInterface;
use Neos\Cache\Exception\InvalidBackendException;

/**
 * Abstracts the task of creating a BackendInterface implementation with it's options.
 */
trait BackendInstantiationTrait
{
    /**
     * @template T of BackendInterface
     * @param class-string<T> $backendObjectName
     * @param array<mixed> $backendOptions
     * @param EnvironmentConfiguration $environmentConfiguration
     * @return T
     * @throws InvalidBackendException
     */
    protected function instantiateBackend(string $backendObjectName, array $backendOptions, EnvironmentConfiguration $environmentConfiguration): BackendInterface
    {
        $backend = new $backendObjectName($environmentConfiguration, $backendOptions);
        /** @phpstan-ignore instanceof.alwaysTrue (Annotations may be wrong) */
        if (!$backend instanceof BackendInterface) {
            throw new InvalidBackendException('"' . $backendObjectName . '" is not a valid cache backend object.', 1216304302);
        }

        return $backend;
    }
}
