<?php
namespace Neos\Flow\Tests\Unit\Reflection\Fixture;

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
 * Aliased class for the Reflection tests
 */
class_alias(ClassWithAlias::class, AliasedClass::class);
