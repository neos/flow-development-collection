<?php

namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP82;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassA;

/**
 * A class using a trait which declares constants
 */
class ClassUsingTraitWithConstants
{
    use TraitWithConstants;

    /* Make sure that this class is proxied, so we can test the proxy compiler */
    #[Flow\Inject]
    protected SingletonClassA $singletonA;

    public function getSingletonA(): SingletonClassA
    {
        return $this->singletonA;
    }
}
