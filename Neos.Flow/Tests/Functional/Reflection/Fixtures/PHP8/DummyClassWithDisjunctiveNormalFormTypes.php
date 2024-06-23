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

use Neos\Flow\Tests\Functional\Reflection\Fixtures\DummyClassWithProperties;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\DummyClassWithTypeHints;
use Neos\Flow\Tests\Functional\Reflection\Fixtures\DummyReadonlyClass;

/**
 * A class with PHP 8.2 disjunctive normal form types
 *
 * @see https://wiki.php.net/rfc/dnf_types
 */
class DummyClassWithDisjunctiveNormalFormTypes
{
    public function dnfTypesA(DummyReadonlyClass | (DummyClassWithTypeHints & DummyClassWithUnionTypeHints) | null $theParameter): void
    {
    }

    public function dnfTypesB(DummyReadonlyClass | (DummyClassWithTypeHints & DummyClassWithUnionTypeHints) | (DummyClassWithTypeHints & DummyClassWithProperties) | null $theParameter): void
    {
    }
}
