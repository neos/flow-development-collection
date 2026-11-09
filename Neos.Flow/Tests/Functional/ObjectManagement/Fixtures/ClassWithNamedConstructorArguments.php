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
 * A prototype class that accepts constructor arguments for testing named argument support
 *
 * @Flow\Scope("prototype")
 */
class ClassWithNamedConstructorArguments
{
    public function __construct(
        public ValueObjectClassA $valueObject,
        public string $stringValue = 'default'
    ) {
    }

    public function getValueObject(): ValueObjectClassA
    {
        return $this->valueObject;
    }

    public function getStringValue(): string
    {
        return $this->stringValue;
    }
}
