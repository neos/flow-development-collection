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
 * A plain class which is matched by the same pointcut expression as LabeledFixtureEnum
 */
class LabeledFixtureThing
{
    public function getLabel(): string
    {
        return 'Thing';
    }
}
