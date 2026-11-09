<?php

namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP81;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A class satisfying the intersection type IdentifiableInterface&NameableInterface
 */
class IdentifiableAndNameable implements IdentifiableInterface, NameableInterface
{
    public function __construct(
        protected string $identifier = 'the-identifier',
        protected string $name = 'the name'
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
