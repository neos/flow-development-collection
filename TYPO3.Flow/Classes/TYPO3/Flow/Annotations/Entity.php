<?php
namespace TYPO3\Flow\Annotations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Marks an object as an entity.
 *
 * Behaves like \Doctrine\ORM\Mapping\Entity so it is interchangeable
 * with that.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Entity
{
    /**
     * Name of the repository class to use for managing the entity.
     * @var string
     */
    public $repositoryClass;

    /**
     * Whether the entity should be read-only.
     * @var boolean
     */
    public $readOnly = false;
}
