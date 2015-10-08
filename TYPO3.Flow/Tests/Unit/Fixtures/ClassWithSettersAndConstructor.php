<?php
namespace TYPO3\Flow\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A dummy class with setters for testing data mapping
 *
 */
class ClassWithSettersAndConstructor
{
    /**
     * @var mixed
     */
    protected $property1;

    /**
     * @var mixed
     */
    protected $property2;

    public function __construct($property1)
    {
        $this->property1 = $property1;
    }

    public function getProperty1()
    {
        return $this->property1;
    }

    public function getProperty2()
    {
        return $this->property2;
    }

    public function setProperty2($property2)
    {
        $this->property2 = $property2;
    }
}
