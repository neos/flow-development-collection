<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A class of scope prototype (but without explicit scope annotation)
 */
class PrototypeClassB
{
    /**
     * @var string
     */
    protected $someProperty;

    /**
     * @param string $someProperty
     * @return void
     */
    public function setSomeProperty($someProperty)
    {
        $this->someProperty = $someProperty;
    }

    /**
     * @return string
     */
    public function getSomeProperty()
    {
        return $this->someProperty;
    }
}
