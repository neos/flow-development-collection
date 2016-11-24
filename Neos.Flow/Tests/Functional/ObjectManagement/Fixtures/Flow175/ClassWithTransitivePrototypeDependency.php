<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\Flow175;

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

class ClassWithTransitivePrototypeDependency
{
    /**
     * @var OuterPrototype
     * @Flow\Inject
     */
    protected $outer;

    public function getTestValue()
    {
        return $this->outer->getInner()->greet('World');
    }
}
