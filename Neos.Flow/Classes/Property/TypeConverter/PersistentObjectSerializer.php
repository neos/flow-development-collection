<?php
namespace Neos\Flow\Property\TypeConverter;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Aspect\PersistenceMagicInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;

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
    protected $sourceTypes = [PersistenceMagicInterface::class];

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
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Convert an entity or valueobject to a string representation (by using the identifier)
     *
     * @param object $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return object the target type
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        $identifier = $this->persistenceManager->getIdentifierByObject($source);
        return $identifier;
    }
}
