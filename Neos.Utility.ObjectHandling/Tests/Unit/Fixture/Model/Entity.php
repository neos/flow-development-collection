<?php
namespace Neos\Utility\ObjectHandling\Tests\Unit\Fixture\Model;

/*
 * This file is part of the Neos.Utility.ObjectHandling package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A model fixture which is used for testing the class schema building
 *
 */
class Entity
{
    /**
     * An identity property
     *
     * @var string
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
     */
    protected $someDate;

    /**
     * @var \SplObjectStorage
     */
    protected $someSplObjectStorage;

    /**
     * A transient string
     *
     * @var string
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
