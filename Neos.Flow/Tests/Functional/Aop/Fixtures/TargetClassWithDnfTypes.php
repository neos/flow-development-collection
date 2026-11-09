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
 * A target class using disjunctive normal form (DNF) types for testing the AOP framework
 */
class TargetClassWithDnfTypes
{
    /**
     * Both the parameter and the return type are DNF types
     */
    public function passThrough((CountableFixtureInterface&LabelableFixtureInterface)|null $subject): (CountableFixtureInterface&LabelableFixtureInterface)|null
    {
        return $subject;
    }

    /**
     * Renders a description of the given DNF typed argument
     */
    public function describe((CountableFixtureInterface&LabelableFixtureInterface)|null $subject): string
    {
        return $subject === null ? 'nothing' : $subject->label() . ':' . $subject->countItems();
    }
}
