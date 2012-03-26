<?php
namespace TYPO3\FLOW3\Tests\Unit\Mvc\Routing\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Router Caching Aspect
 *
 */
class RouterCachingAspectTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @dataProvider subjectsWithAndWithoutObjects()
	 * @test
	 */
	public function containsObjectDetectsObjectsInVariousSituations($expectedResult, $subject) {
		$aspect = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Routing\Aspect\RouterCachingAspect', array('dummy'));
		$actualResult = $aspect->_call('containsObject', $subject);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * Data provider
	 */
	public function subjectsWithAndWithoutObjects() {
		$object = new \stdClass();
		return array(
			array(TRUE, $object),
			array(TRUE, array('foo' => $object)),
			array(TRUE, array('foo' => 'bar', 'baz' => $object)),
			array(TRUE, array('foo' => array('bar' => array('baz' => 'quux', 'here' => $object)))),
			array(FALSE, 'no object'),
			array(FALSE, array('foo' => 'no object')),
			array(FALSE, TRUE)
		);
	}

}
?>
