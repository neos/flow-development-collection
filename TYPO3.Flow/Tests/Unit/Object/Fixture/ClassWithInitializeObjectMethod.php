<?php
namespace TYPO3\Flow\Tests\Object\Fixture;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 */
class ClassWithInitializeObjectMethod
{
    public $reason;

    /**
     * Call the object lifecycle method
     *
     * @param mixed $reason why initializeObject is called.
     */
    public function initializeObject($reason)
    {
        $this->reason = $reason;
    }
}
