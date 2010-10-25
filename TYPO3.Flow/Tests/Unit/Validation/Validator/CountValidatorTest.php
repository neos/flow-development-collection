<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Validation\Validator;

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
 * Testcase for the count validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class CountValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function internalErrorsArrayIsResetOnIsValidCall() {
		$validator = $this->getAccessibleMock('F3\FLOW3\Validation\Validator\CountValidator', array('dummy'), array(), '', FALSE);
		$validator->_set('errors', array('existingError'));
		$validator->isValid(array());
		$this->assertSame(array(), $validator->getErrors());
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function countables() {
		$splObjectStorage = new \SplObjectStorage();
		$splObjectStorage->attach(new \stdClass);
		return array(
			array(array('Foo', 'Bar')),
			array(new \ArrayObject(array('Baz', 'Quux'))),
			array($splObjectStorage)
		);
	}

	/**
	 * @test
	 * @dataProvider countables
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function countValidatorReturnsTrueForValidCountables($countable) {
		$countValidator = new \F3\FLOW3\Validation\Validator\CountValidator();
		$countValidator->setOptions(array('minimum' => 1, 'maximum' => 10));

		$this->assertTrue($countValidator->isValid($countable));
	}

	/**
	 * @test
	 * @dataProvider countables
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function countValidatorReturnsFalseForInvalidCountables($countable) {
		$countValidator = $this->getMock('F3\FLOW3\Validation\Validator\CountValidator', array('addError'));
		$countValidator->setOptions(array('minimum' => 5, 'maximum' => 10));
		$this->assertFalse($countValidator->isValid($countable));
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function nonCountables() {
		$splObjectStorage = new \SplObjectStorage();
		$splObjectStorage->attach(new \stdClass);
		return array(
			array('Bar'),
			array(1),
			array(new \stdClass)
		);
	}

	/**
	 * @test
	 * @dataProvider nonCountables
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function countValidatorReturnsFalseForNonCountables($nonCountable) {
		$countValidator = $this->getMock('F3\FLOW3\Validation\Validator\CountValidator', array('addError'));
		$this->assertFalse($countValidator->isValid($nonCountable));
	}

}

?>