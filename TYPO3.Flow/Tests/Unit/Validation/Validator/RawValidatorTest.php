<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation\Validator;

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
 * Testcase for the raw validator
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RawValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function internalErrorsArrayIsResetOnIsValidCall() {
		$validator = $this->getAccessibleMock('F3\FLOW3\Validation\Validator\RawValidator', array('dummy'));
		$validator->_set('errors', array('existingError'));
		$validator->isValid('foo');
		$this->assertSame(array(), $validator->getErrors());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theRawValidatorAlwaysReturnsTRUE() {
		$rawValidator = new \F3\FLOW3\Validation\Validator\RawValidator();

		$this->assertTrue($rawValidator->isValid('simple1expression'));
		$this->assertTrue($rawValidator->isValid(''));
		$this->assertTrue($rawValidator->isValid(NULL));
		$this->assertTrue($rawValidator->isValid(FALSE));
		$this->assertTrue($rawValidator->isValid(new \ArrayObject()));
	}
}

?>