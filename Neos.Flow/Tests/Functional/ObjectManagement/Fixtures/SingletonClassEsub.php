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
 * A class of scope singleton
 *
 * @Flow\Scope("singleton")
 */
class SingletonClassEsub extends SingletonClassE
{
    /**
     * @var SingletonClassB
     */
    protected $objectB;

    /**
     * @param SingletonClassB $objectB
     */
    public function injectObjectB(SingletonClassB $objectB)
    {
        $this->objectB = $objectB;
    }
}
