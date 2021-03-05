<?php
namespace Neos\Flow\Tests\Functional\Reflection\Fixtures;

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
class DummyClassWithTypeHints
{
    public function methodWithScalarTypeHints(int $integer, string $string)
    {
    }

    public function methodWithArrayTypeHint(array $array)
    {
    }

    /**
     * @param string[] $array
     */
    public function methodWithArrayTypeHintAndAnnotation(array $array)
    {
    }
}
