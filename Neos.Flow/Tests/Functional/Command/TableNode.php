<?php
namespace Neos\Flow\Tests\Functional\Command;

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
 * A helper class for behat scenario parameters, needed when processing
 * behat scenarios/steps in an isolated process
 *
 * @deprecated todo the policy features depending on this handcrafted isolated behat test infrastructure will be refactored and this infrastructure removed.
 * @internal only allowed to be used internally for Neos.Flow behavioral tests!
 */
class TableNode
{
    /**
     * @var string
     */
    protected $hash;

    /**
     * @param string $hash The table source hash string
     */
    public function __construct($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return string The table source hash string
     */
    public function getHash()
    {
        return $this->hash;
    }
}
