<?php

namespace Neos\Flow\Tests\Functional\Aop\Fixtures;

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
 * An enum whose method names are matched by the same pointcut expression which
 * also matches LabeledFixtureThing. Enums cannot be proxied, so the AOP builder
 * must simply skip this one.
 */
enum LabeledFixtureEnum: string
{
    case Aircraft = 'aircraft';
    case Spacecraft = 'spacecraft';

    public function getLabel(): string
    {
        return match ($this) {
            self::Aircraft => 'Aircraft',
            self::Spacecraft => 'Spacecraft',
        };
    }
}
