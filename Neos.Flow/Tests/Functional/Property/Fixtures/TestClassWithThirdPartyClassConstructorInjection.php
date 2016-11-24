<?php
namespace Neos\Flow\Tests\Functional\Property\Fixtures;

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
 * A simple class for PropertyMapper test
 *
 */
class TestClassWithThirdPartyClassConstructorInjection
{

    /**
     * @param \Some\UnknownClass $someDependency
     */
    public function __construct(\Some\UnknownClass $someDependency)
    {
    }
}
