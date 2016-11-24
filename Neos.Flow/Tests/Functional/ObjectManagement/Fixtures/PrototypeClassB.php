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

/**
 * A class of scope prototype (but without explicit scope annotation)
 */
class PrototypeClassB
{
    /**
     * @var string
     */
    protected $someProperty;

    /**
     * @param string $someProperty
     * @return void
     */
    public function setSomeProperty($someProperty)
    {
        $this->someProperty = $someProperty;
    }

    /**
     * @return string
     */
    public function getSomeProperty()
    {
        return $this->someProperty;
    }
}
