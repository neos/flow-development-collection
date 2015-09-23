<?php
namespace TYPO3\Flow\Mvc\Controller;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */


/**
 * The default property mapping configuration is available
 * inside the Argument-object.
 *
 * @api
 */
class MvcPropertyMappingConfiguration extends \TYPO3\Flow\Property\PropertyMappingConfiguration
{
    /**
     * Allow creation of a certain sub property
     *
     * @param string $propertyPath
     * @return \TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfiguration this
     * @api
     */
    public function allowCreationForSubProperty($propertyPath)
    {
        $this->forProperty($propertyPath)->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, true);
        return $this;
    }

    /**
     * Allow modification for a given property path
     *
     * @param string $propertyPath
     * @return \TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfiguration this
     * @api
     */
    public function allowModificationForSubProperty($propertyPath)
    {
        $this->forProperty($propertyPath)->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, true);
        return $this;
    }

    /**
     * Allow override of the target type through a special "__type" parameter
     *
     * @return \TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfiguration this
     * @api
     */
    public function allowOverrideTargetType()
    {
        $this->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED, true);
        return $this;
    }

    /**
     * Set the target type for a certain property. Especially useful
     * if there is an object which has a nested object which is abstract,
     * and you want to instantiate a concrete object instead.
     *
     * @param string $propertyPath
     * @param string $targetType
     * @return \TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfiguration this
     * @api
     */
    public function setTargetTypeForSubProperty($propertyPath, $targetType)
    {
        $this->forProperty($propertyPath)->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_TARGET_TYPE, $targetType);
        return $this;
    }
}
