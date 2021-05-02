<?php
namespace Neos\Flow\Tests\Functional\Reflection\Fixtures\Model;

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
 * A model fixture which is used for testing the class schema building
 *
 * @Flow\Entity
 */
class SuperEntity extends AbstractSuperEntity
{
    /**
     * Just a normal string
     *
     * @var string
     */
    protected $someString;

    /**
     * Just a string that can be null
     *
     * @var string|null
     */
    protected $someNullableString;

    /**
     * Just an int that can be null
     *
     * @var null|int
     */
    protected $someNullableInt;

    /**
     * Just an array of strings that can be null
     *
     * @var array<string>|null
     */
    protected $someNullableArrayOfStrings;

    /**
     * A nullable property with a fully qualified class name
     *
     * @var \DateTimeInterface|null
     */
    protected $aNullableDateTime;
}
