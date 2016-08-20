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

/**
 * This converter transforms persistent objects to strings by returning their (technical) identifier.
 *
 * Unpersisted changes to an object are not serialized, because only the persistence identifier is taken into account
 * as the serialized value.
 *
 * @Flow\Scope("singleton")
 */
class PersistentObjectSerializer extends AbstractTypeConverter
{
    /**
     * @var array
     */
    protected $sourceTypes = array(\TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface::class);

    /**
     * @var string
     */
    protected $targetType = 'string';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Convert an entity or valueobject to a string representation (by using the identifier)
     *
     * @param object $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
     * @return object the target type
     * @throws \TYPO3\Flow\Property\Exception\InvalidTargetException
     * @throws \InvalidArgumentException
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        $identifier = $this->persistenceManager->getIdentifierByObject($source);
        return $identifier;
    }
}
