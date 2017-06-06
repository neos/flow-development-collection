<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

/*
 * This file is part of the TYPO3.Flow package.
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
     * @param TargetClassWithPhp7Features $anObject
     * @return string
     */
    public function methodWithStaticTypeDeclarations(string $aString, int $aNumber, TargetClassWithPhp7Features $anObject)
    {
        return "{$aString} and {$aNumber} and {$anObject}";
    }

//  NOTE: The following methods are commented out for now because they break compatibility with PHP < 7.0
//        We should re-activate them as soon as 7.0 is the minimal required PHP version for Flow
//
//    public function methodWithStaticScalarReturnTypeDeclaration(): string
//    {
//        return 'it works';
//    }
//
//    public function methodWithStaticObjectReturnTypeDeclaration(): TargetClassWithPhp7Features
//    {
//        return $this;
//    }
}
