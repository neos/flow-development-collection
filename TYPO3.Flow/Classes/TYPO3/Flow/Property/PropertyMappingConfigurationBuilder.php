<?php
namespace TYPO3\Flow\Property;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * This builder creates the default configuration for Property Mapping, if no configuration has been passed to the Property Mapper.
 *
 * @Flow\Scope("singleton")
 */
class PropertyMappingConfigurationBuilder
{
    /**
     * Builds the default property mapping configuration.
     *
     * @param string $type the implementation class name of the PropertyMappingConfiguration to instantiate; must be a subclass of PropertyMappingConfiguration
     * @return PropertyMappingConfiguration
     */
    public function build($type = PropertyMappingConfiguration::class)
    {
        $configuration = new $type();

        $configuration->setTypeConverterOptions(TypeConverter\PersistentObjectConverter::class, [
            TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
            TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => true
        ]);
        $configuration->allowAllProperties();

        return $configuration;
    }
}
