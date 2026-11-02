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
 * A target class using pure intersection types for testing the AOP framework
 */
class TargetClassWithIntersectionTypes
{
    /**
     * Both the parameter and the return type are pure intersection types
     */
    public function passThrough(CountableFixtureInterface&LabelableFixtureInterface $subject): CountableFixtureInterface&LabelableFixtureInterface
    {
        return $subject;
    }

    /**
     * Renders a description of the given intersection typed argument
     */
    public function describe(CountableFixtureInterface&LabelableFixtureInterface $subject): string
    {
        return $subject->label() . ':' . $subject->countItems();
    }
}
