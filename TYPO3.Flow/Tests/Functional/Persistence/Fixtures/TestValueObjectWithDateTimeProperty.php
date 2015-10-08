<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A simple value object for persistence tests
 *
 * @Flow\ValueObject
 * @ORM\Table(name="persistence_testvalueobjectwithdatetimeproperty")
 */
class TestValueObjectWithDateTimeProperty
{
    /**
     * @var \DateTime
     */
    protected $value1;

    /**
     * @param \DateTime $value1
     */
    public function __construct($value1)
    {
        $this->value1 = $value1;
    }
}
