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
 * @Flow\ValueObject
 */
class ValueObject
{
    /**
     * Some string
     *
     * @var string
     */
    protected $aString;

    protected $propertyWithoutAnnotation;
}
