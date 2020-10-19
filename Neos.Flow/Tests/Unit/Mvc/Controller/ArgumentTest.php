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
    protected function setUp(): void
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
     */
    public function constructingArgumentWithoutNameThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Mvc\Controller\Argument('', 'Text');
    }

    /**
     * @test
     */
    public function constructingArgumentWithInvalidNameThrowsException()
    {
        $this->expectException(\TypeError::class);
        new Mvc\Controller\Argument(new \ArrayObject(), 'Text');
    }

    /**
     * @test
     */
    public function passingDataTypeToConstructorReallySetsTheDataType()
    {
        self::assertEquals('string', $this->simpleValueArgument->getDataType(), 'The specified data type has not been set correctly.');
        self::assertEquals('someName', $this->simpleValueArgument->getName(), 'The specified name has not been set correctly.');
    }

    /**
     * @test
     */
    public function setRequiredShouldProvideFluentInterfaceAndReallySetRequiredState()
    {
        $returnedArgument = $this->simpleValueArgument->setRequired(true);
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        self::assertTrue($this->simpleValueArgument->isRequired());
    }

    /**
     * @test
     */
    public function setDefaultValueShouldProvideFluentInterfaceAndReallySetDefaultValue()
    {
        $returnedArgument = $this->simpleValueArgument->setDefaultValue('default');
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        self::assertSame('default', $this->simpleValueArgument->getDefaultValue());
    }

    /**
     * @test
     */
    public function setValidatorShouldProvideFluentInterfaceAndReallySetValidator()
    {
        $mockValidator = $this->createMock(ValidatorInterface::class);
        $returnedArgument = $this->simpleValueArgument->setValidator($mockValidator);
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        self::assertSame($mockValidator, $this->simpleValueArgument->getValidator());
    }

    /**
     * @test
     */
    public function setValueProvidesFluentInterface()
    {
        $returnedArgument = $this->simpleValueArgument->setValue(null);
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
    }


    /**
     * @test
     */
    public function setValueUsesNullAsIs()
    {
        $this->simpleValueArgument = new Mvc\Controller\Argument('dummy', 'string');
        $this->simpleValueArgument->setValue(null);
        self::assertNull($this->simpleValueArgument->getValue());
    }

    /**
     * @test
     */
    public function setValueUsesMatchingInstanceAsIs()
    {
        $this->mockPropertyMapper->expects(self::never())->method('convert');
        $this->objectArgument->setValue(new \DateTime());
    }

    protected function setupPropertyMapperAndSetValue()
    {
        $this->mockPropertyMapper->expects(self::once())->method('convert')->with('someRawValue', 'string', $this->mockConfiguration)->will(self::returnValue('convertedValue'));
        $this->mockPropertyMapper->expects(self::once())->method('getMessages')->will(self::returnValue(new FLowError\Result()));
        return $this->simpleValueArgument->setValue('someRawValue');
    }

    /**
     * @test
     */
    public function setValueShouldCallPropertyMapperCorrectlyAndStoreResultInValue()
    {
        $this->setupPropertyMapperAndSetValue();
        self::assertSame('convertedValue', $this->simpleValueArgument->getValue());
    }

    /**
     * @test
     */
    public function setValueShouldBeFluentInterface()
    {
        self::assertSame($this->simpleValueArgument, $this->setupPropertyMapperAndSetValue());
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
        $mockValidator->expects(self::once())->method('validate')->with('convertedValue')->will(self::returnValue($validationMessages));

        $this->simpleValueArgument->setValidator($mockValidator);
        $this->setupPropertyMapperAndSetValue();
        self::assertEquals([$error], $this->simpleValueArgument->getValidationResults()->getErrors());
    }

    /**
     * @test
     */
    public function defaultPropertyMappingConfigurationDoesNotAllowCreationOrModificationOfObjects()
    {
        self::assertNull($this->simpleValueArgument->getPropertyMappingConfiguration()->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertNull($this->simpleValueArgument->getPropertyMappingConfiguration()->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
    }
}
