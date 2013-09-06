<?php
namespace TYPO3\Flow\Tests\Unit\Error;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Debug Exception Handler
 *
 */
class DebugExceptionHandlerTest extends UnitTestCase {

	public function splitExceptionMessageDataProvider() {
		return array(
			array(
				'message' => '',
				'expectedSubject' => '',
				'expectedBody' => ''
			),
			array(
				'message' => 'Some short message',
				'expectedSubject' => 'Some short message',
				'expectedBody' => ''
			),
			array(
				'message' => 'Just one phrase.',
				'expectedSubject' => 'Just one phrase.',
				'expectedBody' => ''
			),
			array(
				'message' => 'First phrase. Second phrase. Third phrase.',
				'expectedSubject' => 'First phrase.',
				'expectedBody' => 'Second phrase. Third phrase.'
			),
			array(
				'message' => 'First line
Second line
Third line',
				'expectedSubject' => 'First line',
				'expectedBody' => 'Second line
Third line'
			),
			array(
				'message' => 'First line' . PHP_EOL . 'Second line',
				'expectedSubject' => 'First line',
				'expectedBody' => 'Second line'
			),
			array(
				'message' => 'Line break and sentence.
				indented body.',
				'expectedSubject' => 'Line break and sentence.',
				'expectedBody' => 'indented body.'
			),
			array(
				'message' => 'Invalid path "foo.bar.baz"! New phrase',
				'expectedSubject' => 'Invalid path "foo.bar.baz"!',
				'expectedBody' => 'New phrase'
			),
			array(
				'message' => 'Question?',
				'expectedSubject' => 'Question?',
				'expectedBody' => ''
			),
			array(
				'message' => 'Question? Answer',
				'expectedSubject' => 'Question?',
				'expectedBody' => 'Answer'
			),
			array(
				'message' => 'Filter() needs arguments if it follows an empty children(): children().filter()',
				'expectedSubject' => 'Filter() needs arguments if it follows an empty children(): children().filter()',
				'expectedBody' => ''
			),
			array(
				'message' => 'children() only supports a single filter group right now, i.e. nothing of the form "filter1, filter2"',
				'expectedSubject' => 'children() only supports a single filter group right now, i.e. nothing of the form "filter1, filter2"',
				'expectedBody' => ''
			),
		);
	}

	/**
	 * @param string $message
	 * @param string $expectedSubject
	 * @param string $expectedBody
	 * @test
	 * @dataProvider splitExceptionMessageDataProvider
	 */
	public function splitExceptionMessageTests($message, $expectedSubject, $expectedBody) {
		$debugExceptionHandler = $this->getAccessibleMock('TYPO3\Flow\Error\DebugExceptionHandler', array('dummy'));

		$expectedResult = array('subject' => $expectedSubject, 'body' => $expectedBody);
		$actualResult = $debugExceptionHandler->_call('splitExceptionMessage', $message);

		$this->assertSame($expectedResult, $actualResult);
	}
}

?>