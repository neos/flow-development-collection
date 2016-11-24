<?php
namespace Neos\Flow\Tests\Functional\Property\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Tests\Functional\Property\TypeConverter;

/**
 * A simple class for PropertyMapper test
 *
 */
class TestClass
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var integer
     */
    protected $size;

    /**
     * @var boolean
     */
    protected $signedCla;

    /**
     * This has no var annotation by intention.
     */
    public $somePublicPropertyWithoutVarAnnotation;

    /**
     * @see TypeConverter\ObjectConverterTest::getTypeOfChildPropertyReturnsCorrectTypeIfThatPropertyIsPubliclyPresent
     * @var float
     */
    public $somePublicProperty;

    /**
     * @see TypeConverter\ObjectConverterTest::convertFromUsesAppropriatePropertyPopulationMethodsInOrderConstructorSetterPublic
     * @var string
     */
    public $propertyMeantForConstructorUsage = 'default';

    /**
     * @see TypeConverter\ObjectConverterTest::convertFromUsesAppropriatePropertyPopulationMethodsInOrderConstructorSetterPublic
     * @var string
     */
    public $propertyMeantForSetterUsage = 'default';

    /**
     * @see TypeConverter\ObjectConverterTest::convertFromUsesAppropriatePropertyPopulationMethodsInOrderConstructorSetterPublic
     * @var string
     */
    public $propertyMeantForPublicUsage = 'default';

    /**
     * @see TypeConverter\ObjectConverterTest::getTypeOfChildPropertyReturnsCorrectTypeIfAConstructorArgumentForThatPropertyIsPresent
     * @see TypeConverter\ObjectConverterTest::convertFromUsesAppropriatePropertyPopulationMethodsInOrderConstructorSetterPublic
     * @param float $dummy
     * @param string $propertyMeantForConstructorUsage
     */
    public function __construct($dummy = null, $propertyMeantForConstructorUsage = null)
    {
        if ($propertyMeantForConstructorUsage !== null) {
            $this->propertyMeantForConstructorUsage = $propertyMeantForConstructorUsage . ' set via Constructor';
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param integer $size
     * @return void
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return boolean
     */
    public function getSignedCla()
    {
        return $this->signedCla;
    }

    /**
     * @param boolean $signedCla
     * @return void
     */
    public function setSignedCla($signedCla)
    {
        $this->signedCla = $signedCla;
    }

    /**
     * @see TypeConverter\ObjectConverterTest::getTypeOfChildPropertyReturnsCorrectTypeIfASetterForThatPropertyIsPresent
     * @param string $value
     */
    public function setAttributeWithStringTypeAnnotation($value)
    {
    }

    /**
     * @see TypeConverter\ObjectConverterTest::convertFromUsesAppropriatePropertyPopulationMethodsInOrderConstructorSetterPublic
     * @param string $value
     */
    public function setPropertyMeantForConstructorUsage($value)
    {
        $this->propertyMeantForConstructorUsage = $value . ' set via Setter';
    }

    /**
     * @see TypeConverter\ObjectConverterTest::convertFromUsesAppropriatePropertyPopulationMethodsInOrderConstructorSetterPublic
     * @param string $value
     */
    public function setPropertyMeantForSetterUsage($value)
    {
        $this->propertyMeantForSetterUsage = $value . ' set via Setter';
    }
}
