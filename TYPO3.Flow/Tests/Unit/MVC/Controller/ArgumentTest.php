<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\MVC\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the MVC Controller Argument
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @covers \F3\FLOW3\MVC\Controller\Argument
 */
class ArgumentTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \F3\FLOW3\MVC\Controller\Argument
	 */
	protected $simpleValueArgument;

	/**
	 * @var \F3\FLOW3\MVC\Controller\Argument
	 */
	protected $objectArgument;

	protected $mockPropertyMapper;
	protected $mockConfigurationBuilder;
	protected $mockConfiguration;

	/**
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		$this->simpleValueArgument = new \F3\FLOW3\MVC\Controller\Argument('someName', 'string');
		$this->objectArgument = new \F3\FLOW3\MVC\Controller\Argument('someName', 'DateTime');

		$this->mockPropertyMapper = $this->getMock('F3\FLOW3\Property\PropertyMapper');
		$this->simpleValueArgument->injectPropertyMapper($this->mockPropertyMapper);
		$this->objectArgument->injectPropertyMapper($this->mockPropertyMapper);

		$this->mockConfigurationBuilder = $this->getMock('F3\FLOW3\Property\PropertyMappingConfigurationBuilder');
		$this->mockConfiguration = $this->getMock('F3\FLOW3\Property\PropertyMappingConfigurationInterface');
		$this->mockConfigurationBuilder->expects($this->any())->method('build')->with('F3\FLOW3\MVC\Controller\MvcPropertyMappingConfiguration')->will($this->returnValue($this->mockConfiguration));

		$this->simpleValueArgument->injectPropertyMappingConfigurationBuilder($this->mockConfigurationBuilder);
		$this->objectArgument->injectPropertyMappingConfigurationBuilder($this->mockConfigurationBuilder);

		$this->simpleValueArgument->initializeObject();
		$this->objectArgument->initializeObject();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \InvalidArgumentException
	 */
	public function constructingArgumentWithoutNameThrowsException() {
		new \F3\FLOW3\MVC\Controller\Argument('', 'Text');
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructingArgumentWithInvalidNameThrowsException() {
		new \F3\FLOW3\MVC\Controller\Argument(new \ArrayObject(), 'Text');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function passingDataTypeToConstructorReallySetsTheDataType() {
		$this->assertEquals('string', $this->simpleValueArgument->getDataType(), 'The specified data type has not been set correctly.');
		$this->assertEquals('someName', $this->simpleValueArgument->getName(), 'The specified name has not been set correctly.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setShortNameProvidesFluentInterface() {
		$returnedArgument = $this->simpleValueArgument->setShortName('x');
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
	}

	public function invalidShortNames() {
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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function shortNameShouldThrowExceptionIfInvalid($invalidShortName) {
		$this->simpleValueArgument->setShortName($invalidShortName);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function shortNameCanBeRetrievedAgain() {
		$this->simpleValueArgument->setShortName('x');
		$this->assertEquals('x', $this->simpleValueArgument->getShortName());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setRequiredShouldProvideFluentInterfaceAndReallySetRequiredState() {
		$returnedArgument = $this->simpleValueArgument->setRequired(TRUE);
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
		$this->assertTrue($this->simpleValueArgument->isRequired());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setShortHelpMessageShouldProvideFluentInterfaceAndReallySetShortHelpMessage() {
		$returnedArgument = $this->simpleValueArgument->setShortHelpMessage('Some Help Message');
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
		$this->assertSame('Some Help Message', $this->simpleValueArgument->getShortHelpMessage());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setShortHelpMessageShouldThrowExceptionIfMessageIsNoString() {
		$this->simpleValueArgument->setShortHelpMessage(NULL);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setDefaultValueShouldProvideFluentInterfaceAndReallySetDefaultValue() {
		$returnedArgument = $this->simpleValueArgument->setDefaultValue('default');
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
		$this->assertSame('default', $this->simpleValueArgument->getDefaultValue());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setValidatorShouldProvideFluentInterfaceAndReallySetValidator() {
		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$returnedArgument = $this->simpleValueArgument->setValidator($mockValidator);
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
		$this->assertSame($mockValidator, $this->simpleValueArgument->getValidator());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setValueProvidesFluentInterface() {
		$returnedArgument = $this->simpleValueArgument->setValue(NULL);
		$this->assertSame($this->simpleValueArgument, $returnedArgument, 'The returned argument is not the original argument.');
	}


	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setValueUsesNullAsIs() {
		$this->simpleValueArgument = new \F3\FLOW3\MVC\Controller\Argument('dummy', 'string');
		$this->simpleValueArgument->setValue(NULL);
		$this->assertNull($this->simpleValueArgument->getValue());
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setValueUsesMatchingInstanceAsIs() {
		$this->mockPropertyMapper->expects($this->never())->method('convert');
		$this->objectArgument->setValue(new \DateTime());
	}

	protected function setupPropertyMapperAndSetValue() {
		$this->mockPropertyMapper->expects($this->once())->method('convert')->with('someRawValue', 'string', $this->mockConfiguration)->will($this->returnValue('convertedValue'));
		return $this->simpleValueArgument->setValue('someRawValue');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setValueShouldCallPropertyMapperCorrectlyAndStoreResultInValue() {
		$this->setupPropertyMapperAndSetValue();
		$this->assertSame('convertedValue', $this->simpleValueArgument->getValue());
		$this->assertTrue($this->simpleValueArgument->isValid());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setValueShouldBeFluentInterface() {
		$this->assertSame($this->simpleValueArgument, $this->setupPropertyMapperAndSetValue());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setValueShouldSetValidationErrorsIfValidatorIsSetAndValidationFailed() {
		$errors = array(
			new \F3\FLOW3\Error\Error('some error', 1234)
		);
		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');
		$mockValidator->expects($this->once())->method('isValid')->with('convertedValue')->will($this->returnValue(FALSE));
		$mockValidator->expects($this->once())->method('getErrors')->will($this->returnValue($errors));

		$this->simpleValueArgument->setValidator($mockValidator);
		$this->setupPropertyMapperAndSetValue();
		$this->assertFalse($this->simpleValueArgument->isValid());
		$this->assertEquals($errors, $this->simpleValueArgument->getValidationErrors());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function defaultPropertyMappingConfigurationShouldBeFetchable() {
		$this->assertSame($this->mockConfiguration, $this->simpleValueArgument->getPropertyMappingConfiguration());
	}


	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function toStringReturnsTheStringVersionOfTheArgumentsValue() {
		$this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue(123));
		$this->simpleValueArgument->setValue('123');

		$this->assertSame((string)$this->simpleValueArgument, '123', 'The returned argument is not a string.');
		$this->assertNotSame((string)$this->simpleValueArgument, 123, 'The returned argument is identical to the set value.');
	}
}
?>