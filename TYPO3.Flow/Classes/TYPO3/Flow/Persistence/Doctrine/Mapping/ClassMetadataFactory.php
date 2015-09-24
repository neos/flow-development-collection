<?php
namespace TYPO3\Flow\Persistence\Doctrine\Mapping;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A factory for Doctrine to create our ClassMetadata instances, aware of
 * the object manager.
 *
 */
class ClassMetadataFactory extends \Doctrine\ORM\Mapping\ClassMetadataFactory
{
    /**
     * Creates a new ClassMetadata instance for the given class name.
     *
     * @param string $className
     * @return \TYPO3\Flow\Persistence\Doctrine\Mapping\ClassMetadata
     */
    protected function newClassMetadataInstance($className)
    {
        return new \TYPO3\Flow\Persistence\Doctrine\Mapping\ClassMetadata($className);
    }
}
