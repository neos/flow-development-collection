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
 * An interface which is introduced into TargetClass03
 */
interface Introduced01Interface
{
    /**
     * @return string
     */
    public function introducedMethod01();

    /**
     * @param string $someString
     * @return string
     */
    public function introducedMethodWithArguments($someString = 'some string');
}
