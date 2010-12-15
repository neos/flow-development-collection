<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Security\Channel;

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
 * Testcase for the Request Hash Service
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class RequestHashServiceTest extends \F3\FLOW3\Tests\UnitTestCase {

	public function dataProviderForGenerateRequestHash() {
		return array(
			// Simple cases
			array(
				array(),
				array(),
			),
			array(
				array('field1'),
				array('field1' => 1),
			),
			array(
				array('field1', 'field2'),
				array(
					'field1' => 1,
					'field2' => 1
				),
			),
			// recursion
			array(
				array('field1', 'field[subfield1]', 'field[subfield2]'),
				array(
					'field1' => 1,
					'field' => array(
						'subfield1' => 1,
						'subfield2' => 1
					)
				),
			),
			// recursion with duplicated field name
			array(
				array('field1', 'field[subfield1]', 'field[subfield2]', 'field1'),
				array(
					'field1' => 1,
					'field' => array(
						'subfield1' => 1,
						'subfield2' => 1
					)
				),
			),
			// Recursion with un-named fields at the end (...[]). There, they should be made explicit by increasing the counter
			array(
				array('field1', 'field[subfield1][]', 'field[subfield1][]', 'field[subfield2]'),
				array(
					'field1' => 1,
					'field' => array(
						'subfield1' => array(
							0 => 1,
							1 => 1
						),
						'subfield2' => 1
					)
				),
			),
		);
	}

	// Data provider for error cases which should throw an exception
	public function dataProviderForGenerateRequestHashWithUnallowedValues() {
		return array(
			// Overriding form fields (string overridden by array)
			array(
				array('field1', 'field2', 'field2[bla]', 'field2[blubb]'),
			),
			array(
				array('field1', 'field2[bla]', 'field2[bla][blubb][blubb]'),
			),
			// Overriding form fields (array overridden by string)
			array(
				array('field1', 'field2[bla]', 'field2[blubb]', 'field2'),
			),
			array(
				array('field1', 'field2[bla][blubb][blubb]', 'field2[bla]'),
			),
			// Empty [] not as last argument
			array(
				array('field1', 'field2[][bla]'),
			)

		);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @dataProvider dataProviderForGenerateRequestHash
	 */
	public function generateRequestHashGeneratesTheCorrectHashesInNormalOperation($input, $expected) {
		$requestHashService = $this->getMock('F3\FLOW3\Security\Channel\RequestHashService', array('serializeAndHashFormFieldArray'));
		$requestHashService->expects($this->once())->method('serializeAndHashFormFieldArray')->with($expected);
		$requestHashService->generateRequestHash($input);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @dataProvider dataProviderForGenerateRequestHashWithUnallowedValues
	 * @expectedException F3\FLOW3\Security\Exception\InvalidArgumentForRequestHashGenerationException
	 */
	public function generateRequestHashThrowsExceptionInWrongCases($input) {
		$requestHashService = $this->getMock('F3\FLOW3\Security\Channel\RequestHashService', array('serializeAndHashFormFieldArray'));
		$requestHashService->generateRequestHash($input);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function serializeAndHashFormFieldArrayWorks() {
		$formFieldArray = array(
			'bla' => array(
				'blubb' => 1,
				'hu' => 1
			)
		);
		$mockHash = '12345';

		$hashService = $this->getMock('F3\FLOW3\Security\Cryptography\HashService', array('generateHmac'));
		$hashService->expects($this->once())->method('generateHmac')->with(serialize($formFieldArray))->will($this->returnValue($mockHash));

		$requestHashService = $this->getAccessibleMock('F3\FLOW3\Security\Channel\RequestHashService', array('dummy'));
		$requestHashService->injectHashService($hashService);

		$expected = serialize($formFieldArray) . $mockHash;
		$actual = $requestHashService->_call('serializeAndHashFormFieldArray', $formFieldArray);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst
	 */
	public function verifyRequestHashSetsHmacVerifiedToFalseIfRequestDoesNotHaveAnHmacArgument() {
		$request = $this->getAccessibleMock('F3\FLOW3\MVC\Web\Request', array('hasArgument', 'setHmacVerified'));
		$request->expects($this->once())->method('hasArgument')->with('__hmac')->will($this->returnValue(FALSE));
		$request->expects($this->once())->method('setHmacVerified')->with(FALSE);
		$requestHashService = new \F3\FLOW3\Security\Channel\RequestHashService;
		$requestHashService->verifyRequest($request);
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Security\Exception\SyntacticallyWrongRequestHashException
	 * @author Sebastian Kurfürst
	 */
	public function verifyRequestHashThrowsExceptionIfHmacIsShortherThan40Characters() {
		$request = $this->getAccessibleMock('F3\FLOW3\MVC\Web\Request', array('hasArgument', 'getArgument', 'setHmacVerified'));
		$request->expects($this->once())->method('hasArgument')->with('__hmac')->will($this->returnValue(TRUE));
		$request->expects($this->once())->method('getArgument')->with('__hmac')->will($this->returnValue('abc'));
		$requestHashService = new \F3\FLOW3\Security\Channel\RequestHashService;
		$requestHashService->verifyRequest($request);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst
	 */
	public function verifyRequestHashValidatesTheHashAndSetsHmacVerifiedToFalseIfHashCouldNotBeVerified() {
		$request = $this->getAccessibleMock('F3\FLOW3\MVC\Web\Request', array('hasArgument', 'getArgument', 'setHmacVerified'));
		$request->expects($this->once())->method('hasArgument')->with('__hmac')->will($this->returnValue(TRUE));
		$request->expects($this->once())->method('getArgument')->with('__hmac')->will($this->returnValue('11111' . '0000000000000000000000000000000000000000'));
		$request->expects($this->once())->method('setHmacVerified')->with(FALSE);

		$hashService = $this->getMock('F3\FLOW3\Security\Cryptography\HashService', array('validateHmac'));
		$hashService->expects($this->once())->method('validateHmac')->with('11111', '0000000000000000000000000000000000000000')->will($this->returnValue(FALSE));

		$requestHashService = new \F3\FLOW3\Security\Channel\RequestHashService;
		$requestHashService->injectHashService($hashService);
		$requestHashService->verifyRequest($request);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst
	 */
	public function verifyRequestHashValidatesTheHashAndSetsHmacVerifiedToTrueIfArgumentsAreIncludedInTheAllowedArgumentList() {
		$data = serialize(array('a' => 1));
		$request = $this->getAccessibleMock('F3\FLOW3\MVC\Web\Request', array('hasArgument', 'getArgument', 'getArguments', 'setHmacVerified'));
		$request->expects($this->once())->method('hasArgument')->with('__hmac')->will($this->returnValue(TRUE));
		$request->expects($this->once())->method('getArgument')->with('__hmac')->will($this->returnValue($data . '0000000000000000000000000000000000000000'));
		$request->expects($this->once())->method('getArguments')->will($this->returnValue(array(
			'__hmac' => 'ABC',
			'__referrer' => '...',
			'a' => 'bla'
		)));
		$request->expects($this->once())->method('setHmacVerified')->with(TRUE);

		$hashService = $this->getMock('F3\FLOW3\Security\Cryptography\HashService', array('validateHmac'));
		$hashService->expects($this->once())->method('validateHmac')->with($data, '0000000000000000000000000000000000000000')->will($this->returnValue(TRUE));

		$requestHashService = $this->getMock('F3\FLOW3\Security\Channel\RequestHashService', array('checkFieldNameInclusion'));
		$requestHashService->expects($this->once())->method('checkFieldNameInclusion')->with(array('a' => 'bla'), array('a' => 1))->will($this->returnValue(TRUE));
		$requestHashService->injectHashService($hashService);
		$requestHashService->verifyRequest($request);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst
	 */
	public function verifyRequestHashValidatesTheHashAndSetsHmacVerifiedToFalseIfNotAllArgumentsAreIncludedInTheAllowedArgumentList() {
		$data = serialize(array('a' => 1));
		$request = $this->getAccessibleMock('F3\FLOW3\MVC\Web\Request', array('hasArgument', 'getArgument', 'getArguments', 'setHmacVerified'));
		$request->expects($this->once())->method('hasArgument')->with('__hmac')->will($this->returnValue(TRUE));
		$request->expects($this->once())->method('getArgument')->with('__hmac')->will($this->returnValue($data . '0000000000000000000000000000000000000000'));
		$request->expects($this->once())->method('getArguments')->will($this->returnValue(array(
			'__hmac' => 'ABC',
			'__referrer' => '...',
			'a' => 'bla',
			'b' => 'blubb'
		)));
		$request->expects($this->once())->method('setHmacVerified')->with(FALSE);

		$hashService = $this->getMock('F3\FLOW3\Security\Cryptography\HashService', array('validateHmac'));
		$hashService->expects($this->once())->method('validateHmac')->with($data, '0000000000000000000000000000000000000000')->will($this->returnValue(TRUE));

		$requestHashService = $this->getMock('F3\FLOW3\Security\Channel\RequestHashService', array('checkFieldNameInclusion'));
		$requestHashService->expects($this->once())->method('checkFieldNameInclusion')->with(array('a' => 'bla', 'b' => 'blubb'), array('a' => 1))->will($this->returnValue(FALSE));
		$requestHashService->injectHashService($hashService);
		$requestHashService->verifyRequest($request);
	}

	/**
	 * Data Provider for checkFieldNameInclusionWorks
	 */
	public function dataProviderForCheckFieldNameInclusion() {
		return array(
			// Simple fields with requestfields = responsefields
			array(
				// Request
				array(
					'a' => 'X',
					'b' => 'X',
					'c' => 'X'
				),
				// Allowed
				array(
					'a' => 1,
					'b' => 1,
					'c' => 1
				),
				// Expected result
				TRUE
			),
			// Simple fields with requestfields < responsefields
			array(
				// Request
				array(
					'a' => 'X',
					'c' => 'X'
				),
				// Allowed
				array(
					'a' => 1,
					'b' => 1,
					'c' => 1
				),
				// Expected result
				TRUE
			),
			// Simple fields with requestfields > responsefields
			array(
				// Request
				array(
					'a' => 'X',
					'b' => 'X',
					'c' => 'X'
				),
				// Allowed
				array(
					'a' => 1,
					'b' => 1
				),
				// Expected result
				FALSE
			),
			// Hierarchical fields with requestfields < responsefields
			array(
				// Request
				array(
					'a' => array(
						'b' => 'X'
					),
					'c' => 'X'
				),
				// Allowed
				array(
					'a' => array(
						'b' => 1,
						'abc' => 1
					),
					'c' => 1
				),
				// Expected result
				TRUE
			),
			// Hierarchical fields with requestfields > responsefields
			array(
				// Request
				array(
					'a' => array(
						'b' => 'X',
						'abc' => 'X'
					),
					'c' => 'X'
				),
				// Allowed
				array(
					'a' => array(
						'b' => 1
					),
					'c' => 1
				),
				// Expected result
				FALSE
			),
			// hierarchical fields with requestfields != responsefields (different types) - 1
			array(
				// Request
				array(
					'a' => array(
						'b' => 'X',
						'c' => 'X'
					),
					'b' => 'X',
					'c' => 'X'
				),
				// Allowed
				array(
					'a' => 1,
					'b' => 1,
					'c' => 1
				),
				// Expected result
				FALSE
			),
			// hierarchical fields with requestfields != responsefields (different types) - 2
			array(
				// Request
				array(
					'a' => 'X',
					'b' => 'X',
					'c' => 'X'
				),
				// Allowed
				array(
					'a' => array(
						'x' => 1,
						'y' => 1
					),
					'b' => 1,
					'c' => 1
				),
				// Expected result
				FALSE
			),
		);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @dataProvider dataProviderForCheckFieldNameInclusion
	 */
	public function checkFieldNameInclusionWorks($requestArguments, $allowedFields, $expectedResult) {
		$requestHashService = $this->getAccessibleMock('F3\FLOW3\Security\Channel\RequestHashService', array('dummy'));
		$this->assertEquals($expectedResult, $requestHashService->_call('checkFieldNameInclusion', $requestArguments, $allowedFields));
	}
}
?>