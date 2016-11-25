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
 * A class of scope prototype
 *
 * @Flow\Scope("prototype")
 * @Flow\Entity
 */
class PrototypeClassA implements PrototypeClassAishInterface
{
    /**
     * @Flow\Transient
     * @var SingletonClassA
     */
    protected $singletonA;

    /**
     * @var string
     */
    protected $someProperty;

    /**
     * @param SingletonClassA $singletonA
     * @return void
     */
    public function injectSingletonA(SingletonClassA $singletonA)
    {
        $this->singletonA = $singletonA;
    }

    /**
     * @return SingletonClassA The singleton class A
     */
    public function getSingletonA()
    {
        return $this->singletonA;
    }

    /**
     * @param string $someProperty The property value
     * @return void
     * @Flow\Session(autoStart=true)
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
