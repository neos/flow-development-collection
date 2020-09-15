<?php
namespace Neos\Flow\Tests\Unit\ObjectManagement\Fixture;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Fixture class for unit tests mainly of the object manager
 *
 */
class StaticFactory
{
    /**
     * @param string $property
     * @return BasicClass
     */
    public static function create(string $property): BasicClass
    {
        $instance = new BasicClass();
        $instance->setSomeProperty($property);
        return $instance;
    }
}
