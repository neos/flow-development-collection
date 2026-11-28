<?php
namespace Neos\Utility\ObjectHandling\Tests\Unit\Fixture;

/*
 * This file is part of the Neos.Utility.ObjectHandling package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Fixture class with public properties
 *
 */

class DummyClassWithPublicProperties
{
    public string $first = 'first';
    public string $second;
    public ?string = null;
}
