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
 * A model fixture which is used for testing the class schema building
 *
 * @Flow\Entity
 */
class EntityExtendingPlainObject extends NonEntity
{
    /**
     * @var string
     */
    protected $anotherPropertyOfTheEntity;
}
