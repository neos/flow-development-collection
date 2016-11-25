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
 * A dummy class with setters for testing data mapping
 *
 */
class ClassWithSetters
{
    /**
     * @var mixed
     */
    public $property1;

    /**
     * @var mixed
     */
    protected $property2;

    /**
     * @var mixed
     */
    public $property3;

    /**
     * @var mixed
     */
    public $property4;

    public function setProperty3($value)
    {
        $this->property3 = $value;
    }

    protected function setProperty4($value)
    {
        $this->property4 = $value;
    }

    public function getProperty2()
    {
        return $this->property2;
    }
}
