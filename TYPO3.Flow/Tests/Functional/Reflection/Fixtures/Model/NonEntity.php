<?php
namespace TYPO3\Flow\Tests\Functional\Reflection\Fixtures\Model;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Just a Plain Old PHP Object as non-abstract base class for the Aggregate Root "EntityExtendingPlainObject"
 */
class NonEntity
{
    /**
     * @var string
     */
    protected $somePropertyOfTheBaseClass;
}
