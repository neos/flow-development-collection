<?php
namespace Neos\Flow\Tests\Unit\Mvc\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Mvc;
use Neos\Flow\Validation\Validator\ValidatorInterface;
use Neos\Error\Messages as FLowError;

/**
 * Testcase for the MVC Controller Argument
 */
class ArgumentTest extends UnitTestCase
{
    /**
     * @var Mvc\Controller\Argument
     */
    protected $simpleValueArgument;

    /**
     * @var Mvc\Controller\Argument
     */
    protected $objectArgument;

    protected $mockPropertyMapper;

    protected $mockConfiguration;

    /**
     */
    public function setUp()
    {
        $this->simpleValueArgument = new Mvc\Controller\Argument('someName', 'string');
        $this->objectArgument = new Mvc\Controller\Argument('someName', 'DateTime');

        $this->mockPropertyMapper = $this->createMock(PropertyMapper::class);
        $this->inject($this->simpleValueArgument, 'propertyMapper', $this->mockPropertyMapper);
        $this->inject($this->objectArgument, 'propertyMapper', $this->mockPropertyMapper);

        $this->mockConfiguration = new Mvc\Controller\MvcPropertyMappingConfiguration();

        $this->inject($this->simpleValueArgument, 'propertyMappingConfiguration', $this->mockConfiguration);
        $this->inject($this->objectArgument, 'propertyMappingConfiguration', $this->mockConfiguration);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function constructingArgumentWithoutNameThrowsException()
    {
        new Mvc\Controller\Argument('', 'Text');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function constructingArgumentWithInvalidNameThrowsException()
    {
        new Mvc\Controller\Argument(new \ArrayObject(), 'Text');
    }

    /**
     * @test
     */
    public function passingDataTypeToConstructorReallySetsTheDataType()
    {
        $this->assertEquals('string', $this->simpleValueArgument->getDataType(), 'The specified data type has not been set correctly.');
        $this->assertEquals('someName', $this->simpleValueArgument->getName(), 'The specified name has not been set correctly.');
    }

    /**
     * @test
     */
    public function setRequiredShouldProvideFluentInterfaceAndReallySetRequiredState()
    {
        $returnedArgument = $this->simpleValueArgument->setRequired(true);
        $this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        $this->assertTrue($this->simpleValueArgument->isRequired());
    }

    /**
     * @test
     */
    public function setDefaultValueShouldProvideFluentInterfaceAndReallySetDefaultValue()
    {
        $returnedArgument = $this->simpleValueArgument->setDefaultValue('default');
        $this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        $this->assertSame('default', $this->simpleValueArgument->getDefaultValue());
    }

    /**
     * @test
     */
    public function setValidatorShouldProvideFluentInterfaceAndReallySetValidator()
    {
        $mockValidator = $this->createMock(ValidatorInterface::class);
        $returnedArgument = $this->simpleValueArgument->setValidator($mockValidator);
        $this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        $this->assertSame($mockValidator, $this->simpleValueArgument->getValidator());
    }

    /**
     * @test
     */
    public function setValueProvidesFluentInterface()
    {
        $returnedArgument = $this->simpleValueArgument->setValue(null);
        $this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
    }


    /**
     * @test
     */
    public function setValueUsesNullAsIs()
    {
        $this->simpleValueArgument = new Mvc\Controller\Argument('dummy', 'string');
        $this->simpleValueArgument->setValue(null);
        $this->assertNull($this->simpleValueArgument->getValue());
    }

    /**
     * @test
     */
    public function setValueUsesMatchingInstanceAsIs()
    {
        $this->mockPropertyMapper->expects($this->never())->method('convert');
        $this->objectArgument->setValue(new \DateTime());
    }

    protected function setupPropertyMapperAndSetValue()
    {
        $this->mockPropertyMapper->expects($this->once())->method('convert')->with('someRawValue', 'string', $this->mockConfiguration)->will($this->returnValue('convertedValue'));
        $this->mockPropertyMapper->expects($this->once())->method('getMessages')->will($this->returnValue(new FLowError\Result()));
        return $this->simpleValueArgument->setValue('someRawValue');
    }

    /**
     * @test
     */
    public function setValueShouldCallPropertyMapperCorrectlyAndStoreResultInValue()
    {
        $this->setupPropertyMapperAndSetValue();
        $this->assertSame('convertedValue', $this->simpleValueArgument->getValue());
    }

    /**
     * @test
     */
    public function setValueShouldBeFluentInterface()
    {
        $this->assertSame($this->simpleValueArgument, $this->setupPropertyMapperAndSetValue());
    }

    /**
     * @test
     */
    public function setValueShouldSetValidationErrorsIfValidatorIsSetAndValidationFailed()
    {
        $error = new FLowError\Error('Some Error', 1234);

        $mockValidator = $this->createMock(ValidatorInterface::class);
        $validationMessages = new FLowError\Result();
        $validationMessages->addError($error);
        $mockValidator->expects($this->once())->method('validate')->with('convertedValue')->will($this->returnValue($validationMessages));

        $this->simpleValueArgument->setValidator($mockValidator);
        $this->setupPropertyMapperAndSetValue();
        $this->assertEquals([$error], $this->simpleValueArgument->getValidationResults()->getErrors());
    }

    /**
     * @test
     */
    public function defaultPropertyMappingConfigurationDoesNotAllowCreationOrModificationOfObjects()
    {
        $this->assertNull($this->simpleValueArgument->getPropertyMappingConfiguration()->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        $this->assertNull($this->simpleValueArgument->getPropertyMappingConfiguration()->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
    }
}
