<?php

declare(strict_types=1);

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
use Neos\Error\Messages as FlowError;
use Neos\Flow\Mvc;
use Neos\Flow\Mvc\Controller\Argument;
use Neos\Flow\Mvc\Controller\MvcPropertyMappingConfiguration;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Validation\Validator\ValidatorInterface;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the MVC Controller Argument
 */
final class ArgumentTest extends UnitTestCase
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
        $this->simpleValueArgument = new Argument('someName', 'string');
        $this->objectArgument = new Argument('someName', 'DateTime');

        $this->mockPropertyMapper = $this->createMock(PropertyMapper::class);
        $this->mockPropertyMapper->method('getMessages')->willReturn(new FlowError\Result());
        $this->inject($this->simpleValueArgument, 'propertyMapper', $this->mockPropertyMapper);
        $this->inject($this->objectArgument, 'propertyMapper', $this->mockPropertyMapper);

        $this->mockConfiguration = new MvcPropertyMappingConfiguration();

        $this->inject($this->simpleValueArgument, 'propertyMappingConfiguration', $this->mockConfiguration);
        $this->inject($this->objectArgument, 'propertyMappingConfiguration', $this->mockConfiguration);
    }

    #[Test]
    public function constructingArgumentWithoutNameThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Argument('', 'Text');
    }

    #[Test]
    public function constructingArgumentWithInvalidNameThrowsException()
    {
        $this->expectException(\TypeError::class);
        new Argument(new \ArrayObject(), 'Text');
    }

    #[Test]
    public function passingDataTypeToConstructorReallySetsTheDataType()
    {
        self::assertEquals('string', $this->simpleValueArgument->getDataType(), 'The specified data type has not been set correctly.');
        self::assertEquals('someName', $this->simpleValueArgument->getName(), 'The specified name has not been set correctly.');
    }

    #[Test]
    public function setRequiredShouldProvideFluentInterfaceAndReallySetRequiredState()
    {
        $returnedArgument = $this->simpleValueArgument->setRequired(true);
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        self::assertTrue($this->simpleValueArgument->isRequired());
    }

    #[Test]
    public function setDefaultValueShouldProvideFluentInterfaceAndReallySetDefaultValue()
    {
        $returnedArgument = $this->simpleValueArgument->setDefaultValue('default');
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        self::assertSame('default', $this->simpleValueArgument->getDefaultValue());
    }

    #[Test]
    public function setValidatorShouldProvideFluentInterfaceAndReallySetValidator()
    {
        $mockValidator = $this->createStub(ValidatorInterface::class);
        $returnedArgument = $this->simpleValueArgument->setValidator($mockValidator);
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        self::assertSame($mockValidator, $this->simpleValueArgument->getValidator());
    }

    #[Test]
    public function setValueProvidesFluentInterface()
    {
        $returnedArgument = $this->simpleValueArgument->setValue(null);
        self::assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
    }

    #[Test]
    public function setValueUsesNullAsIs()
    {
        $this->simpleValueArgument = new Argument('dummy', 'string');
        $this->simpleValueArgument->setValue(null);
        self::assertNull($this->simpleValueArgument->getValue());
    }

    #[Test]
    public function setValueUsesMatchingInstanceAsIs()
    {
        $this->mockPropertyMapper->expects($this->never())->method('convert');
        $this->objectArgument->setValue(new \DateTime());
    }

    protected function setupPropertyMapperAndSetValue()
    {
        $this->mockPropertyMapper->expects($this->once())->method('convert')->with('someRawValue', 'string', $this->mockConfiguration)->willReturn('convertedValue');
        return $this->simpleValueArgument->setValue('someRawValue');
    }

    #[Test]
    public function setValueShouldCallPropertyMapperCorrectlyAndStoreResultInValue()
    {
        $this->setupPropertyMapperAndSetValue();
        self::assertSame('convertedValue', $this->simpleValueArgument->getValue());
    }

    #[Test]
    public function setValueShouldBeFluentInterface()
    {
        self::assertSame($this->simpleValueArgument, $this->setupPropertyMapperAndSetValue());
    }

    #[Test]
    public function setValueShouldSetValidationErrorsIfValidatorIsSetAndValidationFailed()
    {
        $error = new FlowError\Error('Some Error', 1234);

        $mockValidator = $this->createMock(ValidatorInterface::class);
        $validationMessages = new FlowError\Result();
        $validationMessages->addError($error);
        $mockValidator->expects($this->once())->method('validate')->with('convertedValue')->willReturn($validationMessages);

        $this->simpleValueArgument->setValidator($mockValidator);
        $this->setupPropertyMapperAndSetValue();
        self::assertEquals([$error], $this->simpleValueArgument->getValidationResults()->getErrors());
    }

    #[Test]
    public function setValidatorShouldSetValidationErrorsIfValidationFailed()
    {
        $error = new FlowError\Error('Some Error', 1234);

        $mockValidator = $this->createMock(ValidatorInterface::class);
        $validationMessages = new FlowError\Result();
        $validationMessages->addError($error);
        $mockValidator->expects($this->once())->method('validate')->with('convertedValue')->willReturn($validationMessages);

        $this->setupPropertyMapperAndSetValue();
        $this->simpleValueArgument->setValidator($mockValidator);
        self::assertEquals([$error], $this->simpleValueArgument->getValidationResults()->getErrors());
    }

    #[Test]
    public function defaultPropertyMappingConfigurationDoesNotAllowCreationOrModificationOfObjects()
    {
        self::assertNull($this->simpleValueArgument->getPropertyMappingConfiguration()->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        self::assertNull($this->simpleValueArgument->getPropertyMappingConfiguration()->getConfigurationValue(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
    }
}
