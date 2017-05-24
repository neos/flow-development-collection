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

class TargetClassWithPhp71Features
{

    public function methodWithNullableScalarReturnTypeDeclaration(): ?string
    {
        return null;
    }

    public function methodWithNullableObjectReturnTypeDeclaration(): ?TargetClassWithPhp71Features
    {
        return null;
    }

    function __toString()
    {
        return 'TargetClassWithPhp71Features';
    }


}
