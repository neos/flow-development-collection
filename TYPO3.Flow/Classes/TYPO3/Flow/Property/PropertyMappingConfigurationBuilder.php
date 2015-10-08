<?php
namespace TYPO3\Flow\Property;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
     * @param string $type the implementation class name of the PropertyMappingConfiguration to instantiate; must be a subclass of TYPO3\Flow\Property\PropertyMappingConfiguration
     * @return \TYPO3\Flow\Property\PropertyMappingConfiguration
     */
    public function build($type = \TYPO3\Flow\Property\PropertyMappingConfiguration::class)
    {
        $configuration = new $type();

        $configuration->setTypeConverterOptions(\TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::class, array(
            \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true,
            \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => true
        ));
        $configuration->allowAllProperties();

        return $configuration;
    }
}
