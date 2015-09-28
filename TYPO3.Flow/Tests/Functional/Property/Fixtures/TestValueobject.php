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
 * A simple valueobject for PropertyMapper test
 *
 * @Flow\ValueObject
 */
class TestValueobject
{
    /**
     * @var string
     */
    protected $name;

    /**
     *
     * @var integer
     */
    protected $age;

    /**
     *
     * @param string $name
     * @param integer $age
     */
    public function __construct($name, $age)
    {
        $this->name = $name;
        $this->age = $age;
    }

    /**
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * @return integer
     */
    public function getAge()
    {
        return $this->age;
    }
}
