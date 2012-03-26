<?php
namespace TYPO3\FLOW3\Tests\Unit\Mvc\Routing;

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
 * Testcase for the MVC Web Routing StaticRoutePart Class
 *
 */
class StaticRoutePartTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/*                                                                        *
	 * URI matching                                                           *
	 *                                                                        */

	/**
	 * @test
	 */
	public function staticRoutePartDoesNotMatchIfRequestPathIsNullOrEmpty() {
		$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
		$routePart->setName('foo');

		$routePath = NULL;
		$this->assertFalse($routePart->match($routePath), 'Static Route Part should never match if $routePath is NULL.');

		$routePath = '';
		$this->assertFalse($routePart->match($routePath), 'Static Route Part should never match if $routePath is empty.');
	}

	/**
	 * @test
	 */
	public function staticRoutePartDoesNotMatchIfRequestPathIsEmptyEvenIfDefaultValueIsSet() {
		$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$routePart->setDefaultValue('bar');

		$routePath = '';
		$this->assertFalse($routePart->match($routePath), 'Static Route Part should never match if $routePath is empty.');
	}

	/**
	 * @test
	 */
	public function staticRoutePartDoesNotMatchIfUnnamed() {
		$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
		$routePath = 'foo/bar';
		$this->assertFalse($routePart->match($routePath), 'Static Route Part should not match if name is not set.');
	}

	/**
	 * @test
	 */
	public function staticRoutePartDoesNotMatchIfNameIsNotEqualToBeginningOfRequestPath() {
		$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$routePath = 'bar/foo';

		$this->assertFalse($routePart->match($routePath), 'Static Route Part should not match if name is not equal to beginning of request path.');
	}

	/**
	 * @test
	 */
	public function staticRoutePartMatchesIfNameIsEqualToBeginningOfRequestPath() {
		$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$routePath = 'foo/bar';

		$this->assertTrue($routePart->match($routePath), 'Static Route Part should match if name equals beginning of request path.');
	}

	/**
	 * @test
	 */
	public function staticRoutePartDoesNotMatchIfCaseOfRequestPathIsNotEqualToTheName() {
		$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
		$routePart->setName('SomeName');
		$routePath = 'somename';

		$this->assertFalse($routePart->match($routePath), 'Static Route Part should not match if case of name is not equal to case of request path.');
	}

	/**
	 * @test
	 */
	public function valueIsNullAfterUnsuccessfulMatch() {
		$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
		$routePart->setName('foo');

		$routePath = 'foo/bar';
		$this->assertTrue($routePart->match($routePath));

		$routePath = 'bar/foo';
		$this->assertFalse($routePart->match($routePath));
		$this->assertNull($routePart->getValue(), 'Static Route Part value should be NULL after unsuccessful match.');
	}

	/**
	 * @test
	 */
	public function routePathIsNotModifiedAfterUnsuccessfulMatch() {
		$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
		$routePart->setName('bar');

		$routePath = 'foo/bar';
		$this->assertFalse($routePart->match($routePath));
		$this->assertSame('foo/bar', $routePath, 'Static Route Part should not change $routePath on unsuccessful match.');
	}

	/**
	 * @test
	 */
	public function routePathIsShortenedByMatchingPartOnSuccessfulMatch() {
		$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
		$routePart->setName('bar/');
		$routePath = 'bar/foo/test';

		$this->assertTrue($routePart->match($routePath));
		$this->assertSame('foo/test', $routePath, 'Static Route Part should shorten $routePath by matching substring on successful match.');
	}

	/**
	 * @test
	 */
	public function matchResetsValueBeforeProcessingTheRoutePath() {
		$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$routeValues = array();
		$routePart->resolve($routeValues);
		$this->assertSame('foo', $routePart->getValue());

		$routePath = 'foo';
		$routePart->match($routePath);
		$this->assertNull($routePart->getValue(), 'Static Route Part must reset their value to NULL.');
	}

	/*                                                                        *
	 * URI resolving                                                          *
	 *                                                                        */

	/**
	 * @test
	 */
	public function staticRoutePartCanResolveEmptyArray() {
		$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$routeValues = array();

		$this->assertTrue($routePart->resolve($routeValues));
		$this->assertEquals('foo', $routePart->getValue(), 'Static Route Part should resolve empty routeValues-array');
	}

	/**
	 * @test
	 */
	public function staticRoutePartCanResolveNonEmptyArray() {
		$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$routeValues = array('@controller' => 'foo', '@action' => 'bar');

		$this->assertTrue($routePart->resolve($routeValues));
		$this->assertEquals('foo', $routePart->getValue(), 'Static Route Part should resolve non-empty routeValues-array');
	}

	/**
	 * @test
	 */
	public function staticRoutePartDoesNotResolveIfUnnamed() {
		$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
		$routeValues = array();
		$this->assertFalse($routePart->resolve($routeValues), 'Static Route Part should not resolve if name is not set');
	}

	/**
	 * @test
	 */
	public function staticRoutePartDoesNotAlterRouteValuesWhenCallingResolve() {
		$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$routeValues = array('@controller' => 'foo', '@action' => 'bar');

		$this->assertTrue($routePart->resolve($routeValues));
		$this->assertEquals(array('@controller' => 'foo', '@action' => 'bar'), $routeValues, 'when resolve() is called on Static Route Part, specified routeValues-array should never be changed');
	}

	/**
	 * @test
	 */
	public function staticRoutePartLowerCasesValueByDefault() {
		$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
		$routePart->setName('SomeName');
		$routeValues = array();

		$routePart->resolve($routeValues);
		$this->assertEquals('somename', $routePart->getValue(), 'Static Route Part should lowercase the value if lowerCase is true');
	}

	/**
	 * @test
	 */
	public function staticRoutePartDoesNotAlterCaseIfLowerCaseIsFalse() {
		$routePart = new \TYPO3\FLOW3\Mvc\Routing\StaticRoutePart();
		$routePart->setName('SomeName');
		$routePart->setLowerCase(FALSE);
		$routeValues = array();

		$routePart->resolve($routeValues);
		$this->assertEquals('SomeName', $routePart->getValue(), 'By default Static Route Part should not alter the case of name');
	}

}
?>
