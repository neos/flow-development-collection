<?php
namespace TYPO3\Flow\Persistence\Doctrine\Mapping\Driver;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Events;
use TYPO3\Flow\Annotations as Flow;

/**
 * This driver reads the mapping metadata from docblock annotations.
 *
 * It gives precedence to Doctrine annotations but fills gaps from other info
 * if possible:
 *
 * - Entity.repositoryClass is set to the repository found in the class schema
 * - Table.name is set to a sane value
 * - Column.type is set to property type
 * - *.targetEntity is set to property type
 *
 * If a property is not marked as an association the mapping type is set to
 * "object" for objects.
 *
 * @Flow\Scope("singleton")
 */
class FlowAnnotationDriver extends BaseAnnotationDriver
{
    /**
     * Add additional lifecycle callbacks that are "hardcoded"
     *
     * @param \ReflectionClass $class
     * @param \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata
     */
    protected function additionalLifecycleCallbacks(\ReflectionClass $class, \Doctrine\ORM\Mapping\ClassMetadataInfo $metadata)
    {
        $proxyAnnotation = $this->reader->getClassAnnotation($class, \TYPO3\Flow\Annotations\Proxy::class);
        if ($proxyAnnotation === null || $proxyAnnotation->enabled !== false) {
            // FIXME this can be removed again once Doctrine is fixed (see fixMethodsAndAdvicesArrayForDoctrineProxiesCode())
            $metadata->addLifecycleCallback('Flow_Aop_Proxy_fixMethodsAndAdvicesArrayForDoctrineProxies',
                Events::postLoad);
            // FIXME this can be removed again once Doctrine is fixed (see fixInjectedPropertiesForDoctrineProxiesCode())
            $metadata->addLifecycleCallback('Flow_Aop_Proxy_fixInjectedPropertiesForDoctrineProxies', Events::postLoad);
        }
    }

    /**
     * Get a fallback reference column (which for Flow is the Persistence_Object_Identifier)
     *
     * @var \ReflectionProperty
     * @return string
     */
    protected function getFallbackReferenceColumnName(\ReflectionProperty $property)
    {
        return 'persistence_object_identifier';
    }
}
