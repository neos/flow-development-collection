<?php
namespace TYPO3\Eel\Tests\Unit;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\Helper\MathHelper;

/**
 * Tests for MathHelper
 */
class MathHelperTest extends \TYPO3\Flow\Tests\UnitTestCase {

	public function roundExamples() {
		return array(
			'round with default precision' => array(123.4567, NULL, 123),
			'round with 2 digit precision' => array(123.4567, 2, 123.46),
			'round with negative precision' => array(123.4567, -1, 120),
			'round with integer' => array(1234, NULL, 1234)
		);
	}

	/**
	 * @test
	 * @dataProvider roundExamples
	 */
	public function roundWorks($value, $precision, $expected) {
		$helper = new MathHelper();
		$result = $helper->round($value, $precision);
		$this->assertEquals($expected, $result, 'Rounded value did not match', 0.0001);
	}

}
