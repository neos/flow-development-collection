<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

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
     * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA
     */
    protected $singletonA;

    /**
     * @var string
     */
    protected $someProperty;

    /**
     * @param \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA $singletonA
     * @return void
     */
    public function injectSingletonA(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA $singletonA)
    {
        $this->singletonA = $singletonA;
    }

    /**
     * @return \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA The singleton class A
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
