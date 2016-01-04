<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Controller;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the MVC Controller Argument
 *
 * @covers \TYPO3\Flow\Mvc\Controller\Argument
 */
class ArgumentTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Mvc\Controller\Argument
     */
    protected $simpleValueArgument;

    /**
     * @var \TYPO3\Flow\Mvc\Controller\Argument
     */
    protected $objectArgument;

    protected $mockPropertyMapper;

    protected $mockConfiguration;

    /**
     */
    public function setUp()
    {
        $this->simpleValueArgument = new \TYPO3\Flow\Mvc\Controller\Argument('someName', 'string');
        $this->objectArgument = new \TYPO3\Flow\Mvc\Controller\Argument('someName', 'DateTime');

        $this->mockPropertyMapper = $this->getMock(\TYPO3\Flow\Property\PropertyMapper::class);
        $this->inject($this->simpleValueArgument, 'propertyMapper', $this->mockPropertyMapper);
        $this->inject($this->objectArgument, 'propertyMapper', $this->mockPropertyMapper);

        $this->mockConfiguration = new \TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfiguration();

        $this->inject($this->simpleValueArgument, 'propertyMappingConfiguration', $this->mockConfiguration);
        $this->inject($this->objectArgument, 'propertyMappingConfiguration', $this->mockConfiguration);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function constructingArgumentWithoutNameThrowsException()
    {
        new \TYPO3\Flow\Mvc\Controller\Argument('', 'Text');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function constructingArgumentWithInvalidNameThrowsException()
    {
        new \TYPO3\Flow\Mvc\Controller\Argument(new \ArrayObject(), 'Text');
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
    public function setShortNameProvidesFluentInterface()
    {
        $returnedArgument = $this->simpleValueArgument->setShortName('x');
        $this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
    }

    public function invalidShortNames()
    {
        return array(
            array(''),
            array('as'),
            array(5)
        );
    }
    /**
     * @test
     * @dataProvider invalidShortNames
     * @expectedException \InvalidArgumentException
     */
    public function shortNameShouldThrowExceptionIfInvalid($invalidShortName)
    {
        $this->simpleValueArgument->setShortName($invalidShortName);
    }

    /**
     * @test
     */
    public function shortNameCanBeRetrievedAgain()
    {
        $this->simpleValueArgument->setShortName('x');
        $this->assertEquals('x', $this->simpleValueArgument->getShortName());
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
    public function setShortHelpMessageShouldProvideFluentInterfaceAndReallySetShortHelpMessage()
    {
        $returnedArgument = $this->simpleValueArgument->setShortHelpMessage('Some Help Message');
        $this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        $this->assertSame('Some Help Message', $this->simpleValueArgument->getShortHelpMessage());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setShortHelpMessageShouldThrowExceptionIfMessageIsNoString()
    {
        $this->simpleValueArgument->setShortHelpMessage(null);
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
        $mockValidator = $this->getMock(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class);
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
        $this->simpleValueArgument = new \TYPO3\Flow\Mvc\Controller\Argument('dummy', 'string');
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
        $this->mockPropertyMapper->expects($this->once())->method('getMessages')->will($this->returnValue(new \TYPO3\Flow\Error\Result()));
        return $this->simpleValueArgument->setValue('someRawValue');
    }

    /**
     * @test
     */
    public function setValueShouldCallPropertyMapperCorrectlyAndStoreResultInValue()
    {
        $this->setupPropertyMapperAndSetValue();
        $this->assertSame('convertedValue', $this->simpleValueArgument->getValue());
        $this->assertTrue($this->simpleValueArgument->isValid());
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
        $error = new \TYPO3\Flow\Error\Error('Some Error', 1234);

        $mockValidator = $this->getMock(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class);
        $validationMessages = new \TYPO3\Flow\Error\Result();
        $validationMessages->addError($error);
        $mockValidator->expects($this->once())->method('validate')->with('convertedValue')->will($this->returnValue($validationMessages));

        $this->simpleValueArgument->setValidator($mockValidator);
        $this->setupPropertyMapperAndSetValue();
        $this->assertFalse($this->simpleValueArgument->isValid());
        $this->assertEquals(array($error), $this->simpleValueArgument->getValidationResults()->getErrors());
    }

    /**
     * @test
     */
    public function defaultPropertyMappingConfigurationDoesNotAllowCreationOrModificationOfObjects()
    {
        $this->assertNull($this->simpleValueArgument->getPropertyMappingConfiguration()->getConfigurationValue(\TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::class, \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        $this->assertNull($this->simpleValueArgument->getPropertyMappingConfiguration()->getConfigurationValue(\TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::class, \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
    }

    /**
     * @test
     */
    public function setDataTypeProvidesFluentInterfaceAndReallySetsDataType()
    {
        $returnedArgument = $this->simpleValueArgument->setDataType('integer');
        $this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
        $this->assertSame('integer', $this->simpleValueArgument->getDataType(), 'The got dataType is not the set dataType.');
    }
}
