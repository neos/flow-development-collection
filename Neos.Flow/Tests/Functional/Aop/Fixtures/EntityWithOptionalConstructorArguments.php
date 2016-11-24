<?php
namespace Neos\Flow\Tests\Functional\Aop\Fixtures;

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
 * A target class for testing introductions
 *
 * @Flow\Entity
 */
class EntityWithOptionalConstructorArguments
{
    public $argument1;

    public $argument2;

    public $argument3;


    /**
     * @param mixed $argument1
     * @param mixed $argument2
     * @param mixed $argument3
     */
    public function __construct($argument1, $argument2 = null, $argument3 = null)
    {
        $this->argument1 = $argument1;
        $this->argument2 = $argument2;
        $this->argument3 = $argument3;
    }
}
