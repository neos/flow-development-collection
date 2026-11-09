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
 * A class implementing both halves of the intersection type used by the
 * language feature fixtures
 */
class CountableAndLabelableFixture implements CountableFixtureInterface, LabelableFixtureInterface
{
    protected string $labelText;

    protected int $numberOfItems;

    public function __construct(string $labelText = 'fixture', int $numberOfItems = 1)
    {
        $this->labelText = $labelText;
        $this->numberOfItems = $numberOfItems;
    }

    public function countItems(): int
    {
        return $this->numberOfItems;
    }

    public function label(): string
    {
        return $this->labelText;
    }
}
