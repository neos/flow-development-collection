<?php

namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP82;

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
 * A trait with constants, which is a PHP 8.2 feature
 */
trait TraitWithConstants
{
    public const GREETING = 'Hello from the trait';

    protected const ANSWER = 42;

    public function getGreetingFromTrait(): string
    {
        return self::GREETING;
    }

    public function getAnswerFromTrait(): int
    {
        return static::ANSWER;
    }
}
