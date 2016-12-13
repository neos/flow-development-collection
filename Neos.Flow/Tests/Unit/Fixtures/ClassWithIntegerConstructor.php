<?php
namespace Neos\Flow\Fixtures;

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
 * A value object (POPO) with one constructor argument (integer)
 */
class ClassWithIntegerConstructor
{
    /**
     * @var string
     */
    public $value;

    /**
     * ClassWithIntegerConstructor constructor.
     *
     * @param int $value
     */
    public function __construct(int $value)
    {
        $this->value = $value;
    }
}
