<?php /** @noinspection PhpMissingFieldTypeInspection */
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP8;

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
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassB;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassC;

/**
 * A class which has lazy dependencies
 */
class ClassWithLazyDependencies
{
    /**
     * @Flow\Inject
     * @var SingletonClassA
     */
    public $lazyA;

    /**
     * @Flow\Inject
     * @var SingletonClassB|null
     */
    public ?SingletonClassB $lazyB = null;

    /**
     * @Flow\Inject(lazy = false)
     * @var SingletonClassC
     */
    public $eagerC;
}
