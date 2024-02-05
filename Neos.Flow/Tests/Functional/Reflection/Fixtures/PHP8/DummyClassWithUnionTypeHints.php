<?php
namespace Neos\Flow\Tests\Functional\Reflection\Fixtures\PHP8;

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
 * Dummy class for the Reflection tests
 *
 */
class DummyClassWithUnionTypeHints
{
    public function methodWithUnionReturnTypeA(): string|false
    {
    }

    public function methodWithUnionReturnTypesB(): false|DummyClassWithUnionTypeHints
    {
    }

    public function methodWithUnionReturnTypesC(): null|DummyClassWithUnionTypeHints
    {
    }
}
