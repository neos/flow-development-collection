<?php
namespace TYPO3\Flow\Tests\Reflection\Fixture\Model;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

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
