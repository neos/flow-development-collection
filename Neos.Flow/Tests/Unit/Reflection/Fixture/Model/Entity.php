<?php
namespace Neos\Flow\Tests\Reflection\Fixture\Model;

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
class Entity
{
    /**
     * An identity property
     *
     * @var string
     * @Flow\Identity
     */
    protected $someIdentifier;

    /**
     * Just a normal string
     *
     * @var string
     */
    protected $someString;

    /**
     * @var integer
     */
    protected $someInteger;

    /**
     * @var float
     */
    protected $someFloat;

    /**
     * @var \DateTime
     * @Flow\Identity
     */
    protected $someDate;

    /**
     * @var \SplObjectStorage
     * @Flow\Lazy
     */
    protected $someSplObjectStorage;

    /**
     * A transient string
     *
     * @var string
     * @Flow\Transient
     */
    protected $someTransientString;

    /**
     * @var boolean
     */
    protected $someBoolean;

    /**
     * Just an empty constructor
     *
     */
    public function __construct()
    {
    }

    /**
     * Just a dummy method
     *
     * @return void
     */
    public function someDummyMethod()
    {
    }
}
