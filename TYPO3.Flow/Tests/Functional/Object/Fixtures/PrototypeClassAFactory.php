<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A factory which creates PrototypeClassA instances
 */
class PrototypeClassAFactory
{
    /**
     * Creates a new instance of PrototypeClassA
     *
     * @param string $someProperty
     * @return PrototypeClassA
     */
    public function create($someProperty)
    {
        $object = new PrototypeClassA();
        $object->setSomeProperty($someProperty);
        return $object;
    }
}
