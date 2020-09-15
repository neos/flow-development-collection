<?php
namespace Neos\Flow\Tests\Unit\Reflection;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Reflection;

/**
 * Testcase for PropertyReflection
 */
class PropertyReflectionTest extends UnitTestCase
{
    /**
     * @var string
     */
    public $publicProperty = 'I\'m public';

    /**
     * @var string
     */
    protected $protectedProperty = 'abc';

    /**
     * @var string
     */
    private $privateProperty = '123';

    /**
     * @test
     * @expectedException \Neos\Flow\Reflection\Exception
     */
    public function getValueThrowsAnExceptionOnReflectingANonObject()
    {
        $reflectionProperty = new Reflection\PropertyReflection(__CLASS__, 'protectedProperty');
        $reflectionProperty->getValue(__CLASS__);
    }

    /**
     * @test
     */
    public function getValueReturnsValueOfAPublicProperty()
    {
        $reflectionProperty = new Reflection\PropertyReflection(__CLASS__, 'publicProperty');
        $this->assertEquals('I\'m public', $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the value of a public property.');
    }

    /**
     * @test
     */
    public function getValueEvenReturnsValueOfAProtectedProperty()
    {
        $reflectionProperty = new Reflection\PropertyReflection(__CLASS__, 'protectedProperty');
        $this->assertEquals('abc', $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the value of a protected property.');

        $this->protectedProperty = 'def';
        $this->assertEquals('def', $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return "def".');
    }

    /**
     * @test
     */
    public function getValueReturnsValueOfAProtectedPropertyEvenIfItIsAnObject()
    {
        $reflectionProperty = new Reflection\PropertyReflection(__CLASS__, 'protectedProperty');
        $this->protectedProperty = new \ArrayObject(['1', '2', '3']);
        $this->assertEquals($this->protectedProperty, $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the object of our protected property.');

        $this->protectedProperty = $this;
        $this->assertSame($this, $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the reference to $this.');
    }

    /**
     * @test
     */
    public function setValueEvenSetsValueOfAPublicProperty()
    {
        $reflectionProperty = new Reflection\PropertyReflection(__CLASS__, 'publicProperty');
        $reflectionProperty->setValue($this, 'modified');
        $this->assertEquals('modified', $this->publicProperty, 'ReflectionProperty->setValue() did not successfully set the value of a public property.');
    }

    /**
     * @test
     */
    public function getValueEvenReturnsValueOfAPrivateProperty()
    {
        $reflectionProperty = new Reflection\PropertyReflection(__CLASS__, 'privateProperty');
        $this->assertEquals('123', $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the value of a private property.');

        $this->privateProperty = '456';
        $this->assertEquals('456', $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return "456".');
    }

    /**
     * @test
     */
    public function getValueReturnsValueOfAPrivatePropertyEvenIfItIsAnObject()
    {
        $reflectionProperty = new Reflection\PropertyReflection(__CLASS__, 'privateProperty');
        $this->protectedProperty = new \ArrayObject(['1', '2', '3']);
        $this->assertEquals($this->privateProperty, $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the object of our private property.');

        $this->privateProperty = $this;
        $this->assertSame($this, $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the reference to $this.');
    }
}
