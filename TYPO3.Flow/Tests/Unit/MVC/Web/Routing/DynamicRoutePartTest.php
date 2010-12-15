<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\MVC\Web\Routing;

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
 * Testcase for the MVC Web Routing DynamicRoutePart Class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DynamicRoutePartTest extends \F3\FLOW3\Tests\UnitTestCase {

	/*                                                                        *
	 * URI matching                                                           *
	 *                                                                        */

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchIfRequestPathIsNullOrEmpty() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');

		$routePath = NULL;
		$this->assertFalse($routePart->match($routePath), 'Dynamic Route Part should not match if $routePath is NULL.');

		$routePath = '';
		$this->assertFalse($routePart->match($routePath), 'Dynamic Route Part should not match if $routePath is empty.');

	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchEmptyRequestPathEvenIfDefaultValueIsSet() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routePart->setDefaultValue('bar');

		$routePath = '';
		$this->assertFalse($routePart->match($routePath), 'Dynamic Route Part should not match if $routePath is empty.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchIfNameIsNotSet() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePath = 'foo';

		$this->assertFalse($routePart->match($routePath), 'Dynamic Route Part should not match if name is not set.');
	}


	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valueMatchesFirstRequestPathSegmentAfterSuccessfulMatch() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routePart->setDefaultValue('bar');
		$routePart->setSplitString('/');

		$routePath = 'firstSegment/secondSegment';
		$routePart->match($routePath);

		$this->assertEquals('firstSegment', $routePart->getValue(), 'value of Dynamic Route Part should be equal to first request path segment after successful match.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valueIsNullAfterUnsuccessfulMatch() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routePart->setSplitString('/');

		$routePath = 'foo/bar';
		$routePart->match($routePath);

		$routePath = '/bar';
		$routePart->match($routePath);
		$this->assertNull($routePart->getValue(), 'Dynamic Route Part value should be NULL after unsuccessful match.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routePathIsShortenedByOneSegmentAfterSuccessfulMatch() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('bar');
		$routePart->setSplitString('/');

		$routePath = 'bar/foo/test';
		$routePart->match($routePath);

		$this->assertSame('/foo/test', $routePath, 'Dynamic Route Part should shorten request path by one segment on successful match.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRouteDoesNotMatchRequestPathWithMoreThanOneSegmentIfSplitStringIsNotSet() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');

		$routePath = 'foo/bar';

		$this->assertFalse($routePart->match($routePath), 'Dynamic Route Part should not match if request Path has more than one segment and no split string is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRouteDoesNotMatchRequestPathWithMoreThanOneSegmentIfSplitStringIsNotFound() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routePart->setSplitString('not-existing');

		$routePath = 'foo/bar';

		$this->assertFalse($routePart->match($routePath), 'Dynamic Route Part should not match if request Path has more than one segment and does not contain split string.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRouteMatchesRequestPathWithOnlyOneSegmentIfSplitStringIsNotSet() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');

		$routePath = 'bar';

		$this->assertTrue($routePart->match($routePath));
		$this->assertEquals('bar', $routePart->getValue(), 'Dynamic Route Part should match if request Path has only one segment and no split string is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRouteMatchesRequestPathWithOnlyOneSegmentIfSplitStringIsNotFound() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routePart->setSplitString('not-existing');

		$routePath = 'bar';

		$this->assertTrue($routePart->match($routePath));
		$this->assertEquals('bar', $routePart->getValue(), 'Dynamic Route Part should match if request Path has only one segment and does not contain split string.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchIfSplitStringIsAtFirstPosition() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routePart->setSplitString('-');

		$routePath = '-foo/bar';

		$this->assertFalse($routePart->match($routePath), 'Dynamic Route Part should not match if split string is first character of current request path.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartMatchesIfSplitStringContainsMultipleCharactersThatAreFoundInRequestPath() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routePart->setSplitString('_-_');

		$routePath = 'foo_-_bar';
		$this->assertTrue($routePart->match($routePath), 'Dynamic Route Part with a split string of "_-_" should match request path of "foo_-_bar".');
	}

	/*                                                                        *
	 * URI resolving                                                          *
	 *                                                                        */

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotResolveIfNameIsNotSet() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routeValues = array('foo' => 'bar');

		$this->assertFalse($routePart->resolve($routeValues), 'Dynamic Route Part should not resolve if name is not set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartResolvesSimpleValueArray() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routeValues = array('foo' => 'bar');

		$this->assertTrue($routePart->resolve($routeValues));
		$this->assertEquals('bar', $routePart->getValue(), 'Dynamic Route Part should resolve if an element with the same name exists in $routeValues.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotResolveEmptyArray() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routeValues = array();

		$this->assertFalse($routePart->resolve($routeValues), 'Dynamic Route Part should not resolve an empty $routeValues-array.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotResolveEmptyArrayEvenIfDefaultValueIsSet() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routePart->setDefaultValue('defaultValue');
		$routeValues = array();

		$this->assertFalse($routePart->resolve($routeValues), 'Dynamic Route Part should not resolve an empty $routeValues-array even if default Value is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotAlterCaseOfValueWhenCallingResolveByDefault() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('Foo');
		$routeValues = array('Foo' => 'Bar');

		$this->assertTrue($routePart->resolve($routeValues));
		$this->assertEquals('Bar', $routePart->getValue(), 'By default Dynamic Route Part should not alter the case of route values.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartLowerCasesValueIfSpecified() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('Foo');
		$routePart->setLowerCase(TRUE);
		$routeValues = array('Foo' => 'Bar');

		$this->assertTrue($routePart->resolve($routeValues));
		$this->assertEquals('bar', $routePart->getValue(), 'Dynamic Route Part should lowercase the value if lowerCase is true.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveReturnsFalseIfNoCorrespondingValueIsGiven() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routeValues = array('notFoo' => 'bar');

		$this->assertFalse($routePart->resolve($routeValues), 'Dynamic Route Part should not resolve if no element with the same name exists in $routeValues and no default value is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveUnsetsCurrentRouteValueOnSuccessfulResolve() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routeValues = array('foo' => 'bar', 'differentString' => 'value2');

		$this->assertTrue($routePart->resolve($routeValues));
		$this->assertEquals(array('differentString' => 'value2'), $routeValues, 'Dynamic Route Part should unset matching element from $routeValues on successful resolve.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveDoesNotChangeRouteValuesOnUnsuccessfulResolve() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routeValues = array('differentString' => 'bar');

		$this->assertFalse($routePart->resolve($routeValues));
		$this->assertEquals(array('differentString' => 'bar'), $routeValues, 'Dynamic Route Part should not change $routeValues on unsuccessful resolve.');
	}

	/**
	 * Objects cannot be resolved by the standard DynamicRoutePart handler.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveValueReturnsFalseIfTheValueToBeResolvedIsAnObject() {
		$className = $this->buildAccessibleProxy('F3\FLOW3\MVC\Web\Routing\DynamicRoutePart');
		$routePart = new $className;
		$this->assertFalse($routePart->_call('resolveValue', new \stdclass));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routePartValueIsNullAfterUnsuccessfulResolve() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routeValues = array('foo' => 'bar');

		$this->assertTrue($routePart->resolve($routeValues));

		$routeValues = array();
		$this->assertFalse($routePart->resolve($routeValues));
		$this->assertNull($routePart->getValue(), 'Dynamic Route Part value should be NULL when call to resolve() was not successful.');
	}

}
?>
