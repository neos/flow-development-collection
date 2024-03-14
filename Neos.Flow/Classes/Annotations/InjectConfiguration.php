<?php
namespace Neos\Flow\Annotations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Neos\Flow\Configuration\ConfigurationManager;

/**
 * Used to enable property and constructor argument injection for configuration including settings.
 *
 * Flow will build Dependency Injection code for the property and try
 * to inject the configured configuration.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("PROPERTY")
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final readonly class InjectConfiguration
{
    public function __construct(
        /**
         * Path of a configuration which should be injected into the property.
         * Can be specified as anonymous argument: InjectConfiguration("some.path")
         *
         * For type "Settings" this refers to the relative path (excluding the package key)
         *
         * Example: session.name
         */
        public ?string $path = null,
        /**
         * Defines the package key to be used for retrieving settings. If no package key is specified, we'll assume the
         * package to be the same which contains the class where the InjectConfiguration annotation is used.
         *
         * Note: This property is only supported for type "Settings"
         *
         * Example: Neos.Flow
         */
        public ?string $package = null,
        /**
         * Type of Configuration (defaults to "Settings").
         *
         * @param $type ?string one of the ConfigurationManager::CONFIGURATION_TYPE_* constants
         */
        public ?string $type = ConfigurationManager::CONFIGURATION_TYPE_SETTINGS
    ) {
        if ($this->type !== ConfigurationManager::CONFIGURATION_TYPE_SETTINGS && $this->package !== null) {
            throw new \DomainException(sprintf('Invalid usage of "package" with configuration type "%s". Using "package" is only valid for "Settings".', $this->type), 1686910380912);
        }
    }

    /**
     * @param string $fallbackPackageKey fallback, in case no package key is specified {@see self::$package}
     */
    public function getFullConfigurationPath(string $fallbackPackageKey): ?string
    {
        if ($this->type !== ConfigurationManager::CONFIGURATION_TYPE_SETTINGS) {
            return $this->path;
        }
        $packageKey = $this->package ?? $fallbackPackageKey;
        return rtrim($packageKey . '.' . $this->path, '.');
    }
}
