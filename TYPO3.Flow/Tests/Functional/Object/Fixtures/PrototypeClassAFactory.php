<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A factory which creates PrototypeClassA instances
 */
class PrototypeClassAFactory
{
    /**
     * Creates a new instance of PrototypeClassA
     *
     * @param string $someProperty
     * @return \TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassA
     */
    public function create($someProperty)
    {
        $object = new PrototypeClassA();
        $object->setSomeProperty($someProperty);
        return $object;
    }
}
