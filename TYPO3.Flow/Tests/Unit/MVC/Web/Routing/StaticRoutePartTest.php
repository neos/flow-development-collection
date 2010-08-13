<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web\Routing;

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
 * Testcase for the MVC Web Routing StaticRoutePart Class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class StaticRoutePartTest extends \F3\Testing\BaseTestCase {

	/*                                                                        *
	 * URI matching                                                           *
	 *                                                                        */

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfRequestPathIsNullOrEmpty() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('foo');

		$routePath = NULL;
		$this->assertFalse($routePart->match($routePath), 'Static Route Part should never match if $routePath is NULL.');

		$routePath = '';
		$this->assertFalse($routePart->match($routePath), 'Static Route Part should never match if $routePath is empty.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfRequestPathIsEmptyEvenIfDefaultValueIsSet() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$routePart->setDefaultValue('bar');

		$routePath = '';
		$this->assertFalse($routePart->match($routePath), 'Static Route Part should never match if $routePath is empty.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfUnnamed() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePath = 'foo/bar';
		$this->assertFalse($routePart->match($routePath), 'Static Route Part should not match if name is not set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfNameIsNotEqualToBeginningOfRequestPath() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$routePath = 'bar/foo';

		$this->assertFalse($routePart->match($routePath), 'Static Route Part should not match if name is not equal to beginning of request path.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartMatchesIfNameIsEqualToBeginningOfRequestPath() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$routePath = 'foo/bar';

		$this->assertTrue($routePart->match($routePath), 'Static Route Part should match if name equals beginning of request path.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfCaseOfRequestPathIsNotEqualToTheName() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('SomeName');
		$routePath = 'somename';

		$this->assertFalse($routePart->match($routePath), 'Static Route Part should not match if case of name is not equal to case of request path.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valueIsNullAfterUnsuccessfulMatch() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('foo');

		$routePath = 'foo/bar';
		$this->assertTrue($routePart->match($routePath));

		$routePath = 'bar/foo';
		$this->assertFalse($routePart->match($routePath));
		$this->assertNull($routePart->getValue(), 'Static Route Part value should be NULL after unsuccessful match.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routePathIsNotModifiedAfterUnsuccessfulMatch() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('bar');

		$routePath = 'foo/bar';
		$this->assertFalse($routePart->match($routePath));
		$this->assertSame('foo/bar', $routePath, 'Static Route Part should not change $routePath on unsuccessful match.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routePathIsShortenedByMatchingPartOnSuccessfulMatch() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('bar/');
		$routePath = 'bar/foo/test';

		$this->assertTrue($routePart->match($routePath));
		$this->assertSame('foo/test', $routePath, 'Static Route Part should shorten $routePath by matching substring on successful match.');
	}

	/*                                                                        *
	 * URI resolving                                                          *
	 *                                                                        */

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartCanResolveEmptyArray() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$routeValues = array();

		$this->assertTrue($routePart->resolve($routeValues));
		$this->assertEquals('foo', $routePart->getValue(), 'Static Route Part should resolve empty routeValues-array');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartCanResolveNonEmptyArray() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$routeValues = array('@controller' => 'foo', '@action' => 'bar');

		$this->assertTrue($routePart->resolve($routeValues));
		$this->assertEquals('foo', $routePart->getValue(), 'Static Route Part should resolve non-empty routeValues-array');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotResolveIfUnnamed() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routeValues = array();
		$this->assertFalse($routePart->resolve($routeValues), 'Static Route Part should not resolve if name is not set');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotAlterRouteValuesWhenCallingResolve() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$routeValues = array('@controller' => 'foo', '@action' => 'bar');

		$this->assertTrue($routePart->resolve($routeValues));
		$this->assertEquals(array('@controller' => 'foo', '@action' => 'bar'), $routeValues, 'when resolve() is called on Static Route Part, specified routeValues-array should never be changed');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotAlterCaseOfNameWhenCallingResolveByDefault() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('SomeName');
		$routeValues = array();

		$routePart->resolve($routeValues);
		$this->assertEquals('SomeName', $routePart->getValue(), 'By default Static Route Part should not alter the case of name');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartLowerCasesValueIfSpecified() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('SomeName');
		$routePart->setLowerCase(TRUE);
		$routeValues = array();

		$routePart->resolve($routeValues);
		$this->assertEquals('somename', $routePart->getValue(), 'Static Route Part should lowercase the value if lowerCase is true');
	}

}
?>
