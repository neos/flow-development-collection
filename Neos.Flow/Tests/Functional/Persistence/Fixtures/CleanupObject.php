<?php
namespace Neos\Flow\Tests\Functional\Persistence\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class CleanupObject
{
    /**
     * @var boolean
     */
    protected $state = false;

    public function toggleState()
    {
        $this->state = !$this->state;
    }

    /**
     * @return boolean
     */
    public function getState()
    {
        return $this->state;
    }
}
