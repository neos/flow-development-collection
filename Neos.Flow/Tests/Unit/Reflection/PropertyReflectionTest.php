<?php

declare(strict_types=1);

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
use Neos\Flow\Reflection\Exception;
use Neos\Flow\Reflection\PropertyReflection;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for PropertyReflection
 */
final class PropertyReflectionTest extends UnitTestCase
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

    #[Test]
    public function getValueThrowsAnExceptionOnReflectingANonObject()
    {
        $this->expectException(Exception::class);
        $reflectionProperty = new PropertyReflection(__CLASS__, 'protectedProperty');
        $reflectionProperty->getValue(__CLASS__);
    }

    #[Test]
    public function getValueReturnsValueOfAPublicProperty()
    {
        $reflectionProperty = new PropertyReflection(__CLASS__, 'publicProperty');
        self::assertEquals('I\'m public', $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the value of a public property.');
    }

    #[Test]
    public function getValueEvenReturnsValueOfAProtectedProperty()
    {
        $reflectionProperty = new PropertyReflection(__CLASS__, 'protectedProperty');
        self::assertEquals('abc', $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the value of a protected property.');

        $this->protectedProperty = 'def';
        self::assertEquals('def', $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return "def".');
    }

    #[Test]
    public function getValueReturnsValueOfAProtectedPropertyEvenIfItIsAnObject()
    {
        $reflectionProperty = new PropertyReflection(__CLASS__, 'protectedProperty');
        $this->protectedProperty = new \ArrayObject(['1', '2', '3']);
        self::assertEquals($this->protectedProperty, $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the object of our protected property.');

        $this->protectedProperty = $this;
        self::assertSame($this, $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the reference to $this.');
    }

    #[Test]
    public function setValueEvenSetsValueOfAPublicProperty()
    {
        $reflectionProperty = new PropertyReflection(__CLASS__, 'publicProperty');
        $reflectionProperty->setValue($this, 'modified');
        self::assertEquals('modified', $this->publicProperty, 'ReflectionProperty->setValue() did not successfully set the value of a public property.');
    }

    #[Test]
    public function getValueEvenReturnsValueOfAPrivateProperty()
    {
        $reflectionProperty = new PropertyReflection(__CLASS__, 'privateProperty');
        self::assertEquals('123', $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the value of a private property.');

        $this->privateProperty = '456';
        self::assertEquals('456', $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return "456".');
    }

    #[Test]
    public function getValueReturnsValueOfAPrivatePropertyEvenIfItIsAnObject()
    {
        $reflectionProperty = new PropertyReflection(__CLASS__, 'privateProperty');
        $this->protectedProperty = new \ArrayObject(['1', '2', '3']);
        self::assertEquals($this->privateProperty, $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the object of our private property.');

        $this->privateProperty = $this;
        self::assertSame($this, $reflectionProperty->getValue($this), 'ReflectionProperty->getValue($this) did not return the reference to $this.');
    }
}
