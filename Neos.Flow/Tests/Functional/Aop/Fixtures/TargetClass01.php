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

use Neos\Flow\Tests\Functional\Aop\Fixtures;

/**
 * A target class for testing the AOP framework
 *
 */
class TargetClass01 implements SayHelloInterface
{
    /**
     * @var Fixtures\Name
     */
    protected $currentName;

    /**
     * @var string
     */
    public $constructorResult = '';

    /**
     * @var integer
     */
    public $initializeObjectCallCounter = 0;

    /**
     *
     */
    public function __construct()
    {
        $this->constructorResult .= 'AVRO RJ100';
    }

    /**
     *
     */
    public function initializeObject()
    {
        $this->initializeObjectCallCounter++;
    }

    /**
     * @return string
     */
    public function sayHello()
    {
        return 'Hello';
    }

    /**
     * @return string
     */
    public function sayWhatFlowIs()
    {
        return 'Flow is';
    }

    /**
     * @return string
     */
    public function saySomethingSmart()
    {
        return 'Two plus two makes five!';
    }

    /**
     * @param boolean $throwException
     * @return string
     * @throws \Exception
     */
    public function sayHelloAndThrow($throwException)
    {
        if ($throwException) {
            throw new \Exception();
        }
        return 'Hello';
    }

    /**
     * @param string $name
     * @return string
     */
    public function greet($name)
    {
        return 'Hello, ' . $name;
    }

    /**
     * @param Fixtures\Name $name
     * @return string
     */
    public function greetObject(Fixtures\Name $name)
    {
        return 'Hello, ' . $name;
    }

    /**
     * @param \SplObjectStorage $names
     * @return string
     */
    public function greetMany(\SplObjectStorage $names)
    {
        $greet = '';
        foreach ($names as $name) {
            $greet .= $name;
        }
        return 'Hello, ' . $greet;
    }

    /**
     *
     * @return string
     */
    public function getCurrentName()
    {
        return $this->currentName;
    }

    /**
     *
     * @param Fixtures\Name $name
     * @return void
     */
    public function setCurrentName(Fixtures\Name $name = null)
    {
        $this->currentName = $name;
    }

    /**
     * @return string
     */
    public static function someStaticMethod()
    {
        return 'I won\'t take any advice';
    }
}
