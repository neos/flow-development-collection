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

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Marks an object as an entity.
 *
 * Behaves like \Doctrine\ORM\Mapping\Entity so it is interchangeable
 * with that.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("CLASS")
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Entity
{
    /**
     * Name of the repository class to use for managing the entity.
     * @var string|null
     */
    public $repositoryClass;

    /**
     * Whether the entity should be read-only.
     * @var boolean
     */
    public $readOnly = false;

    public function __construct(?string $repositoryClass = null, bool $readOnly = false)
    {
        $this->repositoryClass = $repositoryClass;
        $this->readOnly = $readOnly;
    }
}
