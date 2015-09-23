<?php
namespace TYPO3\Flow\Tests\Functional\Property\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A simple entity for PropertyMapper test
 *
 * @Flow\Entity
 */
class TestEntitySubclassWithNewField extends TestEntity
{
    /**
     * @var string
     */
    protected $testField;

    /**
     * @param string $testField
     */
    public function setTestField($testField)
    {
        $this->testField = $testField;
    }

    /**
     * @return string
     */
    public function getTestField()
    {
        return $this->testField;
    }
}
