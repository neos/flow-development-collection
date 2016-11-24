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

class TargetClassWithPhp7Features
{
    /**
     * Test scalar type declarations on parameters
     *
     * The return type declaration causes syntax errors below PHP 7.0 but is supported by the reflection service and
     * proxy builder in Flow.
     *
     * @param string $aString
     * @param int $aNumber
     * @return string
     */
    public function methodWithStaticTypeDeclarations(string $aString, int $aNumber)
    {
        return "{$aString} and {$aNumber}";
    }
}
