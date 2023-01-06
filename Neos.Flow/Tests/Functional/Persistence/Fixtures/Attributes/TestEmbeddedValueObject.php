<?php
namespace Neos\Flow\Tests\Functional\Persistence\Fixtures\Attributes;

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
 * A simple embedded value object for persistence tests
 */
#[Flow\ValueObject(embedded: true)]
class TestEmbeddedValueObject
{

    public function __construct(protected string $value = '')
    {
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
