<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation;

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
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the validator resolver
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ValidatorResolverTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveValidatorObjectNameReturnsFalseIfValidatorCantBeResolved() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('isObjectRegistered')->with('Foo')->will($this->returnValue(FALSE));
		$mockObjectManager->expects($this->at(1))->method('isObjectRegistered')->with('F3\FLOW3\Validation\Validator\FooValidator')->will($this->returnValue(FALSE));

		$validatorResolver = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('dummy'), array($mockObjectManager));
		$this->assertSame(FALSE, $validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveValidatorObjectNameReturnsTheGivenArgumentIfAnObjectOfThatNameIsRegistered() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('isObjectRegistered')->with('Foo')->will($this->returnValue(TRUE));

		$validatorResolver = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('dummy'), array($mockObjectManager));
		$this->assertSame('Foo', $validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveValidatorObjectNameCanResolveShortNamesOfBuiltInValidators() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('isObjectRegistered')->with('Foo')->will($this->returnValue(FALSE));
		$mockObjectManager->expects($this->at(1))->method('isObjectRegistered')->with('F3\FLOW3\Validation\Validator\FooValidator')->will($this->returnValue(TRUE));

		$validatorResolver = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('dummy'), array($mockObjectManager));
		$this->assertSame('F3\FLOW3\Validation\Validator\FooValidator', $validatorResolver->_call('resolveValidatorObjectName', 'Foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createValidatorResolvesAndReturnsAValidatorAndPassesTheGivenOptions() {
		$className = uniqid('Test');
		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ObjectValidatorInterface', array(), array(), $className);
		$mockValidator->expects($this->once())->method('setOptions')->with(array('foo' => 'bar'));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->any())->method('getObject')->with($className)->will($this->returnValue($mockValidator));

		$validatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver',array('resolveValidatorObjectName'), array($mockObjectManager));
		$validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with($className)->will($this->returnValue($className));
		$validator = $validatorResolver->createValidator($className, array('foo' => 'bar'));
		$this->assertSame($mockValidator, $validator);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createValidatorReturnsNullIfAValidatorCouldNotBeResolved() {
		$validatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver',array('resolveValidatorObjectName'), array(), '', FALSE);
		$validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with('Foo')->will($this->returnValue(FALSE));
		$validator = $validatorResolver->createValidator('Foo', array('foo' => 'bar'));
		$this->assertNull($validator);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBaseValidatorCachesTheResultOfTheBuildBaseValidatorChainCalls() {
		$mockChainValidator = $this->getMock('F3\FLOW3\Validation\Validator\ChainValidator', array(), array(), '', FALSE);

		$validatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array('buildBaseValidatorChain'), array(), '', FALSE);
		$validatorResolver->expects($this->once())->method('buildBaseValidatorChain')->with('F3\Virtual\Foo')->will($this->returnValue($mockChainValidator));

		$result = $validatorResolver->getBaseValidatorChain('F3\Virtual\Foo');
		$this->assertSame($mockChainValidator, $result, '#1');

		$result = $validatorResolver->getBaseValidatorChain('F3\Virtual\Foo');
		$this->assertSame($mockChainValidator, $result, '#2');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildMethodArgumentsValidatorChainsDetectsValidateAnnotationsAndRegistersNewValidatorsForEachArgument() {
		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('fooAction'), array(), '', FALSE);

		$methodTagsValues = array(
			'param' => array(
				'string $arg1',
				'array $arg2',
			),
			'validate' => array(
				'$arg1 Foo(bar = baz), Bar',
				'$arg2 Quux'
			)
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodTagsValues));

		$mockFooValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		$mockBarValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		$mockQuuxValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);

		$chain1 = $this->getMock('F3\FLOW3\Validation\Validator\ChainValidator', array(), array(), '', FALSE);
		$chain1->expects($this->at(0))->method('addValidator')->with($mockFooValidator);
		$chain1->expects($this->at(1))->method('addValidator')->with($mockBarValidator);

		$chain2 = $this->getMock('F3\FLOW3\Validation\Validator\ChainValidator', array(), array(), '', FALSE);
		$chain2->expects($this->at(0))->method('addValidator')->with($mockQuuxValidator);

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');

		$mockArguments = new \F3\FLOW3\MVC\Controller\Arguments($mockObjectFactory);
		$mockArguments->addArgument(new \F3\FLOW3\MVC\Controller\Argument('arg1'));
		$mockArguments->addArgument(new \F3\FLOW3\MVC\Controller\Argument('arg2'));

		$mockArguments['arg2'] = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);

		$validatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockFooValidator));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('Chain')->will($this->returnValue($chain1));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('Bar')->will($this->returnValue($mockBarValidator));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('Quux')->will($this->returnValue($mockQuuxValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('Chain')->will($this->returnValue($chain2));

		$validatorResolver->injectReflectionService($mockReflectionService);

		$result = $validatorResolver->buildMethodArgumentsValidatorChains(get_class($mockController), 'fooAction');
		$this->assertSame(array('arg1' => $chain1, 'arg2' => $chain2), $result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildBaseValidatorChainAddsCustomValidatorToTheReturnedChain() {
		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface');

		$mockChainValidator = $this->getMock('F3\FLOW3\Validation\Validator\ChainValidator', array(), array(), '', FALSE);
		$mockChainValidator->expects($this->once())->method('addValidator')->with($mockValidator);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('getObject')->with('F3\FLOW3\Validation\Validator\ChainValidator')->will($this->returnValue($mockChainValidator));
		$mockObjectManager->expects($this->at(1))->method('getObject')->with('F3\Virtual\FooValidator')->will($this->returnValue($mockValidator));

		$validatorResolver = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('resolveValidatorObjectName'), array($mockObjectManager));
		$validatorResolver->expects($this->once())->method('resolveValidatorObjectName')->with('F3\Virtual\FooValidator')->will($this->returnValue('F3\Virtual\FooValidator'));

		$result = $validatorResolver->_call('buildBaseValidatorChain', 'F3\Virtual\Foo');
		$this->assertSame($mockChainValidator, $result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildBaseValidatorChainAddsValidatorsDefinedByAnnotationsInTheClassToTheReturnedChain() {
		$mockObject = $this->getMock('stdClass');
		$className = get_class($mockObject);

		$propertyTagsValues = array(
			'foo' => array(
				'var' => array('string'),
				'validate' => array(
					'Foo(bar = baz), Bar',
					'Baz'
				)
			),
			'bar' => array(
				'var' => array('integer'),
				'validate' => array(
					'Quux'
				)
			)
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->at(0))->method('getClassPropertyNames')->with($className)->will($this->returnValue(array('foo', 'bar')));
		$mockReflectionService->expects($this->at(1))->method('getPropertyTagsValues')->with($className, 'foo')->will($this->returnValue($propertyTagsValues['foo']));
		$mockReflectionService->expects($this->at(2))->method('getPropertyTagsValues')->with($className, 'bar')->will($this->returnValue($propertyTagsValues['bar']));

		$mockObjectValidator = $this->getMock('F3\FLOW3\Validation\Validator\GenericObjectValidator', array(), array(), '', FALSE);

		$mockChainValidator = $this->getMock('F3\FLOW3\Validation\Validator\ChainValidator', array(), array(), '', FALSE);
		$mockChainValidator->expects($this->once())->method('addValidator')->with($mockObjectValidator);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('getObject')->with('F3\FLOW3\Validation\Validator\ChainValidator')->will($this->returnValue($mockChainValidator));

		$validatorResolver = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('resolveValidatorObjectName', 'createValidator'), array($mockObjectManager));
		$validatorResolver->injectReflectionService($mockReflectionService);

		$validatorResolver->expects($this->at(0))->method('resolveValidatorObjectName')->with($className . 'Validator')->will($this->returnValue(FALSE));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('GenericObject')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('Bar')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('Baz')->will($this->returnValue($mockObjectValidator));
		$validatorResolver->expects($this->at(5))->method('createValidator')->with('Quux')->will($this->returnValue($mockObjectValidator));

		$result = $validatorResolver->_call('buildBaseValidatorChain', $className);
		$this->assertSame($mockChainValidator, $result);
	}

	/**
	 * test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildMethodArgumentsValidatorChainsBuildsAChainFromValidateAnnotationsOfTheSpecifiedMethod() {
		$mockObject = $this->getMock('stdClass', array('fooMethod'), array(), '', FALSE);

		$methodTagsValues = array(
			'param' => array(
				'string $arg1',
				'array $arg2',
			),
			'validate' => array(
				'$arg1 Foo(bar = baz), Bar',
				'$arg2 Quux'
			)
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodTagsValues));

		$mockFooValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		$mockBarValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);
		$mockQuuxValidator = $this->getMock('F3\FLOW3\Validation\Validator\ValidatorInterface', array(), array(), '', FALSE);

		$chain1 = $this->getMock('F3\FLOW3\Validation\Validator\ChainValidator', array(), array(), '', FALSE);
		$chain1->expects($this->at(0))->method('addValidator')->with($mockFooValidator);
		$chain1->expects($this->at(1))->method('addValidator')->with($mockBarValidator);

		$chain2 = $this->getMock('F3\FLOW3\Validation\Validator\ChainValidator', array(), array(), '', FALSE);
		$chain2->expects($this->at(0))->method('addValidator')->with($mockQuuxValidator);

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');

		$mockArguments = new \F3\FLOW3\MVC\Controller\Arguments($mockObjectFactory);
		$mockArguments->addArgument(new \F3\FLOW3\MVC\Controller\Argument('arg1'));
		$mockArguments->addArgument(new \F3\FLOW3\MVC\Controller\Argument('arg2'));

		$mockArguments['arg2'] = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);

		$validatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array('createValidator'), array(), '', FALSE);
		$validatorResolver->expects($this->at(0))->method('createValidator')->with('Foo', array('bar' => 'baz'))->will($this->returnValue($mockFooValidator));
		$validatorResolver->expects($this->at(1))->method('createValidator')->with('Chain')->will($this->returnValue($chain1));
		$validatorResolver->expects($this->at(2))->method('createValidator')->with('Bar')->will($this->returnValue($mockBarValidator));
		$validatorResolver->expects($this->at(3))->method('createValidator')->with('Quux')->will($this->returnValue($mockQuuxValidator));
		$validatorResolver->expects($this->at(4))->method('createValidator')->with('Chain')->will($this->returnValue($chain2));

		$validatorResolver->injectReflectionService($mockReflectionService);

		$result = $validatorResolver->buildMethodArgumentsValidatorChains(get_class($mockController), 'fooAction');
		$this->assertSame(array('arg1' => $chain1, 'arg2' => $chain2), $result);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveValidatorObjectNameCallsUnifyDataType() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockValidator = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('unifyDataType'), array($mockObjectManager));
		$mockValidator->expects($this->once())->method('unifyDataType')->with('someDataType');
		$mockValidator->_call('resolveValidatorObjectName', 'someDataType');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function unifyDataTypeCorrectlyRenamesPHPDataTypes() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockValidator = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('dummy'), array($mockObjectManager), '', FALSE);
		$this->assertEquals('Integer', $mockValidator->_call('unifyDataType', 'integer'));
		$this->assertEquals('Integer', $mockValidator->_call('unifyDataType', 'int'));
		$this->assertEquals('Text', $mockValidator->_call('unifyDataType', 'string'));
		$this->assertEquals('Array', $mockValidator->_call('unifyDataType', 'array'));
		$this->assertEquals('Float', $mockValidator->_call('unifyDataType', 'float'));
		$this->assertEquals('Float', $mockValidator->_call('unifyDataType', 'double'));
		$this->assertEquals('Boolean', $mockValidator->_call('unifyDataType', 'boolean'));
		$this->assertEquals('Boolean', $mockValidator->_call('unifyDataType', 'bool'));
		$this->assertEquals('Boolean', $mockValidator->_call('unifyDataType', 'bool'));
		$this->assertEquals('Number', $mockValidator->_call('unifyDataType', 'number'));
		$this->assertEquals('Number', $mockValidator->_call('unifyDataType', 'numeric'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function unifyDataTypeRenamesMixedToRaw() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockValidator = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Validation\ValidatorResolver'), array('dummy'), array($mockObjectManager), '', FALSE);
		$this->assertEquals('Raw', $mockValidator->_call('unifyDataType', 'mixed'));
	}
}

?>