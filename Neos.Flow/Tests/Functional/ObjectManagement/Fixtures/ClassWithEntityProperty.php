<?php

namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

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

/**
 * A prototype class with an entity property (Issue #3493)
 * Before the fix, this class would NOT get serialization code
 * because the proxy builder was too eager in skipping it.
 *
 * @Flow\Scope("prototype")
 */
class ClassWithEntityProperty
{
    /**
     * @var SimpleEntity
     */
    public $entity;

    /**
     * @var string
     */
    public $someValue;

    public function __construct(SimpleEntity $entity, string $someValue = 'default')
    {
        $this->entity = $entity;
        $this->someValue = $someValue;
    }
}
