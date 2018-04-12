<?php
namespace Neos\Flow\Tests\Functional\Reflection\Fixtures;

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
 * Dummy class for the Reflection tests
 *
 */
class DummyClassWithProperties
{
    /**
     * The var annotation is intentional as "int" to check if the reflection service normalizes variable types.
     *
     * @var int
     */
    protected $intProperty;

    /**
     * This should result in the same type string as the "intProperty".
     *
     * @var integer
     */
    protected $integerProperty;

    /**
     * Same as for int/integer for bool.
     *
     * @var bool
     */
    protected $boolProperty;

    /**
     * @var boolean
     */
    protected $booleanProperty;
}
