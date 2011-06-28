<?php
namespace TYPO3\FLOW3\Tests\Unit\Validation\Validator;

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

require_once('AbstractValidatorTestcase.php');

/**
 * Testcase for the count validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class CountValidatorTest extends \TYPO3\FLOW3\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\FLOW3\Validation\Validator\CountValidator';

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
		$this->validatorOptions(array('minimum' => 1, 'maximum' => 10));
		$this->assertFalse($this->validator->validate($countable)->hasErrors());
	}

	/**
	 * @test
	 * @dataProvider countables
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function countValidatorReturnsFalseForInvalidCountables($countable) {
		$this->validatorOptions(array('minimum' => 5, 'maximum' => 10));
		$this->assertTrue($this->validator->validate($countable)->hasErrors());
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
		$this->assertTrue($this->validator->validate($nonCountable)->hasErrors());
	}
}
?>