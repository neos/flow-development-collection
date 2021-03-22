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

use Neos\Flow\Configuration\ConfigurationManager;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Used to enable property injection for configuration including settings.
 *
 * Flow will build Dependency Injection code for the property and try
 * to inject the configured configuration.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("PROPERTY")
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class InjectConfiguration
{
    /**
     * Path of a configuration which should be injected into the property.
     * Can be specified as anonymous argument: InjectConfiguration("some.path")
     *
     * For type "Settings" this refers to the relative path (excluding the package key)
     *
     * Example: session.name
     *
     * @var string|null
     */
    public $path;

    /**
     * Defines the package key to be used for retrieving settings. If no package key is specified, we'll assume the
     * package to be the same which contains the class where the InjectConfiguration annotation is used.
     *
     * Note: This property is only supported for type "Settings"
     *
     * Example: Neos.Flow
     *
     * @var string|null
     */
    public $package;

    /**
     * Type of Configuration (defaults to "Settings").
     *
     * @var string one of the ConfigurationManager::CONFIGURATION_TYPE_* constants
     */
    public $type = ConfigurationManager::CONFIGURATION_TYPE_SETTINGS;

    public function __construct(?string $path = null, ?string $package = null, ?string $type = null)
    {
        $this->path = $path;
        $this->package = $package;
        if ($type !== null) {
            $this->type = $type;
        }
    }
}
