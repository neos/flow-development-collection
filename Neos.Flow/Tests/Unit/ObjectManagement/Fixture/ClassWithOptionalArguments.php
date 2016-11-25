<?php
namespace Neos\Flow\Tests\Unit\ObjectManagement\Fixture;

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
 * Fixture class for various unit tests (mainly the package- and object
 * manager)
 *
 */
class ClassWithOptionalArguments
{
    public $argument1;
    public $argument2;
    public $argument3;

    /**
     * Dummy constructor which accepts up to three arguments
     *
     * @param mixed $argument1
     * @param mixed $argument2
     * @param mixed $argument3
     */
    public function __construct($argument1 = null, $argument2 = null, $argument3 = null)
    {
        $this->argument1 = $argument1;
        $this->argument2 = $argument2;
        $this->argument3 = $argument3;
    }
}
