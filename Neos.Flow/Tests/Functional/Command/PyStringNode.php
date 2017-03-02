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
 */
class PyStringNode
{
    /**
     * @var string
     */
    protected $rawString;

    /**
     * @param string $rawString The raw string as written in the behat feature file
     */
    public function __construct($rawString)
    {
        $this->rawString = $rawString;
    }

    /**
     * @return string The raw string as written in the behat feature file
     */
    public function getRaw()
    {
        return $this->rawString;
    }
}
