<?php
namespace TYPO3\Flow\Property\TypeConverter;

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
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * TypeConverter which converts generic objects to arrays by converting and returning
 *
 */
class ArrayFromObjectConverter extends AbstractTypeConverter
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var array<string>
     */
    protected $sourceTypes = array('object');

    /**
     * @var string
     */
    protected $targetType = 'array';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * Convert all properties in the source array
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        $gettableProperties = ObjectAccess::getGettableProperties($source);
        $propertiesToConvert = array();
        foreach ($gettableProperties as $propertyName => $gettableProperty) {
            if (is_object($gettableProperty)) {
                $propertiesToConvert[$propertyName] = $gettableProperty;
            }
        }

        return $propertiesToConvert;
    }

    /**
     * @param string $targetType
     * @param string $propertyName
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     */
    public function getTypeOfChildProperty($targetType, $propertyName, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration)
    {
        return 'array';
    }


    /**
     * Actually convert from $source to $targetType, taking into account the fully
     * built $convertedChildProperties and $configuration.
     *
     * The return value can be one of three types:
     * - an arbitrary object, or a simple type (which has been created while mapping).
     *   This is the normal case.
     * - NULL, indicating that this object should *not* be mapped (i.e. a "File Upload" Converter could return NULL if no file has been uploaded, and a silent failure should occur.
     * - An instance of \TYPO3\Flow\Error\Error -- This will be a user-visible error message later on.
     * Furthermore, it should throw an Exception if an unexpected failure (like a security error) occurred or a configuration issue happened.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return mixed|\TYPO3\Flow\Error\Error the target type, or an error object if a user-error occurred
     * @throws \TYPO3\Flow\Property\Exception\TypeConverterException thrown in case a developer error occurred
     * @api
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), PropertyMappingConfigurationInterface $configuration = null)
    {
        $properties = ObjectAccess::getGettableProperties($source);
        if ($source instanceof \Doctrine\ORM\Proxy\Proxy) {
            $className = get_parent_class($source);
        } else {
            $className = get_class($source);
        }

        $properties = array_merge($properties, $convertedChildProperties);

        if ($source instanceof \TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface) {
            $properties['__identity'] = $this->persistenceManager->getIdentifierByObject($source);
        }

        $properties['__type'] = $className;

        return $properties;
    }
}
