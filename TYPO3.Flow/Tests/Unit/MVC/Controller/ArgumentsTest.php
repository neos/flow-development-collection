<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Controller;

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
 * Testcase for the MVC Controller Arguments
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ArgumentsTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addingAnArgumentManuallyWorks() {
		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($this->getMock('F3\FLOW3\Object\ObjectFactoryInterface'));
		$newArgument = new \F3\FLOW3\MVC\Controller\Argument('argumentName1234', 'Text');

		$arguments->addArgument($newArgument);
		$this->assertSame($newArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addingAnArgumentReplacesArgumentWithSameName() {
		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($this->getMock('F3\FLOW3\Object\ObjectFactoryInterface'));

		$firstArgument = new \F3\FLOW3\MVC\Controller\Argument('argumentName1234', 'Text');
		$arguments->addArgument($firstArgument);

		$secondArgument = new \F3\FLOW3\MVC\Controller\Argument('argumentName1234', 'Text');
		$arguments->addArgument($secondArgument);

		$this->assertSame($secondArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addingArgumentThroughArrayAccessWorks() {
		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($this->getMock('F3\FLOW3\Object\ObjectFactoryInterface'));
		$argument = new \F3\FLOW3\MVC\Controller\Argument('argumentName1234', 'Text');
		$arguments[] = $argument;
		$this->assertTrue($arguments->hasArgument('argumentName1234'), 'Added argument does not exist.');
		$this->assertSame($argument, $arguments->getArgument('argumentName1234'), 'Added and retrieved arguments are not the same.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function retrievingArgumentThroughArrayAccessWorks() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')
				->with('F3\FLOW3\MVC\Controller\Argument', 'someArgument', 'Text')
				->will($this->returnValue(new \F3\FLOW3\MVC\Controller\Argument('someArgument', 'Text')));

		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($mockObjectFactory);
		$newArgument = $arguments->addNewArgument('someArgument');
		$this->assertSame($newArgument, $arguments['someArgument'], 'Argument retrieved by array access is not the one we added.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArgumentWithNonExistingArgumentNameThrowsException() {
		$arguments = $this->objectManager->getObject('F3\FLOW3\MVC\Controller\Arguments');
		try {
			$arguments->getArgument('someArgument');
			$this->fail('getArgument() did not throw an exception although the specified argument does not exist.');
		} catch (\F3\FLOW3\MVC\Exception\NoSuchArgumentException $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function issetReturnsCorrectResult() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')
				->with('F3\FLOW3\MVC\Controller\Argument', 'someArgument', 'Text')
				->will($this->returnValue(new \F3\FLOW3\MVC\Controller\Argument('someArgument', 'Text')));

		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($mockObjectFactory);
		$this->assertFalse(isset($arguments['someArgument']), 'isset() did not return FALSE.');
		$arguments->addNewArgument('someArgument');
		$this->assertTrue(isset($arguments['someArgument']), 'isset() did not return TRUE.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArgumentNamesReturnsNamesOfAddedArguments() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface');
		$mockObjectFactory->expects($this->exactly(3))->method('create')
				->will($this->onConsecutiveCalls(
						new \F3\FLOW3\MVC\Controller\Argument('first', 'Text'),
						new \F3\FLOW3\MVC\Controller\Argument('second', 'Text'),
						new \F3\FLOW3\MVC\Controller\Argument('third', 'Text')
				));

		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($mockObjectFactory);
		$arguments->addNewArgument('first');
		$arguments->addNewArgument('second');
		$arguments->addNewArgument('third');

		$expectedArgumentNames = array('first', 'second', 'third');
		$this->assertEquals($expectedArgumentNames, $arguments->getArgumentNames(), 'Returned argument names were not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArgumentShortNamesReturnsShortNamesOfAddedArguments() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface');
		$mockObjectFactory->expects($this->exactly(3))->method('create')
				->will($this->onConsecutiveCalls(
						new \F3\FLOW3\MVC\Controller\Argument('first', 'Text'),
						new \F3\FLOW3\MVC\Controller\Argument('second', 'Text'),
						new \F3\FLOW3\MVC\Controller\Argument('third', 'Text')
				));

		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($mockObjectFactory);
		$argument = $arguments->addNewArgument('first')->setShortName('a');
		$arguments->addNewArgument('second')->setShortName('b');
		$arguments->addNewArgument('third')->setShortName('c');

		$expectedShortNames = array('a', 'b', 'c');
		$this->assertEquals($expectedShortNames, $arguments->getArgumentShortNames(), 'Returned argument short names were not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addNewArgumentCreatesAndAddsNewArgument() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')
				->with('F3\FLOW3\MVC\Controller\Argument', 'dummyName', 'Text')
				->will($this->returnValue(new \F3\FLOW3\MVC\Controller\Argument('dummyName', 'Text')));

		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($mockObjectFactory);
		$addedArgument = $arguments->addNewArgument('dummyName');
		$this->assertType('F3\FLOW3\MVC\Controller\Argument', $addedArgument, 'addNewArgument() either did not add a new argument or did not return it.');

		$retrievedArgument = $arguments['dummyName'];
		$this->assertSame($addedArgument, $retrievedArgument, 'The added and the retrieved argument are not the same.');

		$this->assertEquals('dummyName', $addedArgument->getName(), 'The name of the added argument is not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addNewArgumentAssumesTextDataTypeByDefault() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')
				->with('F3\FLOW3\MVC\Controller\Argument', 'dummyName', 'Text')
				->will($this->returnValue(new \F3\FLOW3\MVC\Controller\Argument('someArgument', 'Text')));

		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($mockObjectFactory);

		$addedArgument = $arguments->addNewArgument('dummyName');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function addNewArgumentCanAddArgumentsMarkedAsRequired() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')
				->with('F3\FLOW3\MVC\Controller\Argument', 'dummyName', 'Text')
				->will($this->returnValue(new \F3\FLOW3\MVC\Controller\Argument('someArgument', 'Text')));

		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($mockObjectFactory);

		$addedArgument = $arguments->addNewArgument('dummyName', 'Text', TRUE);
		$this->assertTrue($addedArgument->isRequired(), 'addNewArgument() did not create an argument that is marked as required.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function addNewArgumentCanAddArgumentsMarkedAsOptionalWithDefaultValues() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')
				->with('F3\FLOW3\MVC\Controller\Argument', 'dummyName', 'Text')
				->will($this->returnValue(new \F3\FLOW3\MVC\Controller\Argument('someArgument', 'Text')));

		$arguments = new \F3\FLOW3\MVC\Controller\Arguments($mockObjectFactory);

		$defaultValue = 'Default Value 42';
		$addedArgument = $arguments->addNewArgument('dummyName', 'Text', FALSE, $defaultValue);
		$this->assertEquals($defaultValue, $addedArgument->getValue(), 'addNewArgument() did not store the default value in the argument.');
	}

	/**
	 * @test
	 * @expectedException \LogicException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function callingInvalidMethodThrowsException() {
		$arguments = $this->objectManager->getObject('F3\FLOW3\MVC\Controller\Arguments');
		$arguments->nonExistingMethod();
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function removeAllClearsAllArguments() {
		$arguments = $this->objectManager->getObject('F3\FLOW3\MVC\Controller\Arguments');
		$arguments->addArgument(new \F3\FLOW3\MVC\Controller\Argument('foo', 'Text'));

		$arguments->removeAll();

		$this->assertFalse($arguments->hasArgument('foo'));
	}
}
?>