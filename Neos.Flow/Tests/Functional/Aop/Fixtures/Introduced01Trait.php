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
 * A trait which is introduced into TargetClass01
 */
trait Introduced01Trait
{
    /**
     * @return string
     */
    public function sayHello()
    {
        return 'Hello from trait';
    }

    /**
     * @return string
     */
    public function introducedTraitMethod()
    {
        return 'I\'m the traitor';
    }
}
