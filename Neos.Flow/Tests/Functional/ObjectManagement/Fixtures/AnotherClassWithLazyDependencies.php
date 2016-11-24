<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

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

/**
 * A second class which has lazy dependencies
 */
class AnotherClassWithLazyDependencies
{
    /**
     * @Flow\Inject
     * @var SingletonClassA
     */
    public $lazyA;

    /**
     * @Flow\Inject
     * @var SingletonClassB
     */
    public $lazyB;

    /**
     * @Flow\Inject(lazy = FALSE)
     * @var SingletonClassC
     */
    public $eagerC;
}
