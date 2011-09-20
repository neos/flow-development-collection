<?php
namespace TYPO3\FLOW3\Tests\Unit\MVC\Web\Routing;

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
 */
class DynamicRoutePartTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\Routing\DynamicRoutePart
	 */
	protected $dynamicRoutPart;

	/**
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $mockPersistenceManager;

	public function setUp() {
		$this->dynamicRoutPart = $this->getAccessibleMock('TYPO3\FLOW3\MVC\Web\Routing\DynamicRoutePart', array('dummy'));

		$this->mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$this->dynamicRoutPart->_set('persistenceManager', $this->mockPersistenceManager);
	}

	/*                                                                        *
	 * URI matching                                                           *
	 *                                                                        */

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchIfRequestPathIsNullOrEmpty() {
		$this->dynamicRoutPart->setName('foo');

		$routePath = NULL;
		$this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if $routePath is NULL.');

		$routePath = '';
		$this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if $routePath is empty.');

	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchEmptyRequestPathEvenIfDefaultValueIsSet() {
		$this->dynamicRoutPart->setName('foo');
		$this->dynamicRoutPart->setDefaultValue('bar');

		$routePath = '';
		$this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if $routePath is empty.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchIfNameIsNotSet() {
		$routePath = 'foo';

		$this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if name is not set.');
	}


	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valueMatchesFirstRequestPathSegmentAfterSuccessfulMatch() {
		$this->dynamicRoutPart->setName('foo');
		$this->dynamicRoutPart->setDefaultValue('bar');
		$this->dynamicRoutPart->setSplitString('/');

		$routePath = 'firstSegment/secondSegment';
		$this->dynamicRoutPart->match($routePath);

		$this->assertEquals('firstSegment', $this->dynamicRoutPart->getValue(), 'value of Dynamic Route Part should be equal to first request path segment after successful match.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valueIsNullAfterUnsuccessfulMatch() {
		$this->dynamicRoutPart->setName('foo');
		$this->dynamicRoutPart->setSplitString('/');

		$routePath = 'foo/bar';
		$this->dynamicRoutPart->match($routePath);

		$routePath = '/bar';
		$this->dynamicRoutPart->match($routePath);
		$this->assertNull($this->dynamicRoutPart->getValue(), 'Dynamic Route Part value should be NULL after unsuccessful match.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routePathIsShortenedByOneSegmentAfterSuccessfulMatch() {
		$this->dynamicRoutPart->setName('bar');
		$this->dynamicRoutPart->setSplitString('/');

		$routePath = 'bar/foo/test';
		$this->dynamicRoutPart->match($routePath);

		$this->assertSame('/foo/test', $routePath, 'Dynamic Route Part should shorten request path by one segment on successful match.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRouteDoesNotMatchRequestPathWithMoreThanOneSegmentIfSplitStringIsNotSet() {
		$this->dynamicRoutPart->setName('foo');

		$routePath = 'foo/bar';

		$this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if request Path has more than one segment and no split string is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRouteDoesNotMatchRequestPathWithMoreThanOneSegmentIfSplitStringIsNotFound() {
		$this->dynamicRoutPart->setName('foo');
		$this->dynamicRoutPart->setSplitString('not-existing');

		$routePath = 'foo/bar';

		$this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if request Path has more than one segment and does not contain split string.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRouteMatchesRequestPathWithOnlyOneSegmentIfSplitStringIsNotSet() {
		$this->dynamicRoutPart->setName('foo');

		$routePath = 'bar';

		$this->assertTrue($this->dynamicRoutPart->match($routePath));
		$this->assertEquals('bar', $this->dynamicRoutPart->getValue(), 'Dynamic Route Part should match if request Path has only one segment and no split string is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRouteMatchesRequestPathWithOnlyOneSegmentIfSplitStringIsNotFound() {
		$this->dynamicRoutPart->setName('foo');
		$this->dynamicRoutPart->setSplitString('not-existing');

		$routePath = 'bar';

		$this->assertTrue($this->dynamicRoutPart->match($routePath));
		$this->assertEquals('bar', $this->dynamicRoutPart->getValue(), 'Dynamic Route Part should match if request Path has only one segment and does not contain split string.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchIfSplitStringIsAtFirstPosition() {
		$this->dynamicRoutPart->setName('foo');
		$this->dynamicRoutPart->setSplitString('-');

		$routePath = '-foo/bar';

		$this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if split string is first character of current request path.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartMatchesIfSplitStringContainsMultipleCharactersThatAreFoundInRequestPath() {
		$this->dynamicRoutPart->setName('foo');
		$this->dynamicRoutPart->setSplitString('_-_');

		$routePath = 'foo_-_bar';
		$this->assertTrue($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part with a split string of "_-_" should match request path of "foo_-_bar".');
	}

	/*                                                                        *
	 * URI resolving                                                          *
	 *                                                                        */

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotResolveIfNameIsNotSet() {
		$routeValues = array('foo' => 'bar');

		$this->assertFalse($this->dynamicRoutPart->resolve($routeValues), 'Dynamic Route Part should not resolve if name is not set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartResolvesSimpleValueArray() {
		$this->dynamicRoutPart->setName('foo');
		$routeValues = array('foo' => 'bar');

		$this->assertTrue($this->dynamicRoutPart->resolve($routeValues));
		$this->assertEquals('bar', $this->dynamicRoutPart->getValue(), 'Dynamic Route Part should resolve if an element with the same name exists in $routeValues.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotResolveEmptyArray() {
		$this->dynamicRoutPart->setName('foo');
		$routeValues = array();

		$this->assertFalse($this->dynamicRoutPart->resolve($routeValues), 'Dynamic Route Part should not resolve an empty $routeValues-array.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotResolveEmptyArrayEvenIfDefaultValueIsSet() {
		$this->dynamicRoutPart->setName('foo');
		$this->dynamicRoutPart->setDefaultValue('defaultValue');
		$routeValues = array();

		$this->assertFalse($this->dynamicRoutPart->resolve($routeValues), 'Dynamic Route Part should not resolve an empty $routeValues-array even if default Value is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotAlterCaseOfValueWhenCallingResolveByDefault() {
		$this->dynamicRoutPart->setName('Foo');
		$routeValues = array('Foo' => 'Bar');

		$this->assertTrue($this->dynamicRoutPart->resolve($routeValues));
		$this->assertEquals('Bar', $this->dynamicRoutPart->getValue(), 'By default Dynamic Route Part should not alter the case of route values.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartLowerCasesValueIfLowerCaseIsTrue() {
		$this->dynamicRoutPart->setName('Foo');
		$this->dynamicRoutPart->setLowerCase(TRUE);
		$routeValues = array('Foo' => 'Bar');

		$this->assertTrue($this->dynamicRoutPart->resolve($routeValues));
		$this->assertEquals('bar', $this->dynamicRoutPart->getValue(), 'Dynamic Route Part should lowercase the value if lowerCase is true.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveReturnsFalseIfNoCorrespondingValueIsGiven() {
		$this->dynamicRoutPart->setName('foo');
		$routeValues = array('notFoo' => 'bar');

		$this->assertFalse($this->dynamicRoutPart->resolve($routeValues), 'Dynamic Route Part should not resolve if no element with the same name exists in $routeValues and no default value is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveUnsetsCurrentRouteValueOnSuccessfulResolve() {
		$this->dynamicRoutPart->setName('foo');
		$routeValues = array('foo' => 'bar', 'differentString' => 'value2');

		$this->assertTrue($this->dynamicRoutPart->resolve($routeValues));
		$this->assertEquals(array('differentString' => 'value2'), $routeValues, 'Dynamic Route Part should unset matching element from $routeValues on successful resolve.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveRecursivelyUnsetsCurrentRouteValueOnSuccessfulResolve() {
		$this->dynamicRoutPart->setName('foo.bar.baz');
		$routeValues = array('foo' => array('bar' => array('baz' => 'should be removed', 'otherKey' => 'should stay')), 'differentString' => 'value2');

		$this->assertTrue($this->dynamicRoutPart->resolve($routeValues));
		$this->assertEquals(array('foo' => array('bar' => array('otherKey' => 'should stay')), 'differentString' => 'value2'), $routeValues);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveDoesNotChangeRouteValuesOnUnsuccessfulResolve() {
		$this->dynamicRoutPart->setName('foo');
		$routeValues = array('differentString' => 'bar');

		$this->assertFalse($this->dynamicRoutPart->resolve($routeValues));
		$this->assertEquals(array('differentString' => 'bar'), $routeValues, 'Dynamic Route Part should not change $routeValues on unsuccessful resolve.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveValueReturnsTrueAndSetTheValueToTheIdentifierIfTheValueToBeResolvedIsAnObject() {
		$object = new \stdClass();
		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue('TheIdentifier'));
		$this->assertTrue($this->dynamicRoutPart->_call('resolveValue', $object));
		$this->assertSame('TheIdentifier', $this->dynamicRoutPart->getValue());
	}

	/**
	 * Objects that are unknown to the persistence manager cannot be resolved by the standard DynamicRoutePart handler.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveValueReturnsFalseIfTheValueToBeResolvedIsAnObjectThatIsUnknownToThePersistenceManager() {
		$object = new \stdClass();
		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue(NULL));
		$this->assertFalse($this->dynamicRoutPart->_call('resolveValue', $object));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveValueReturnsFalseIfTheValueToBeResolvedIsAnObjectWithAnIdentifierThatIsNoString() {
		$object = new \stdClass();
		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue(array('foo', 'bar')));
		$this->assertFalse($this->dynamicRoutPart->_call('resolveValue', $object));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routePartValueIsNullAfterUnsuccessfulResolve() {
		$this->dynamicRoutPart->setName('foo');
		$routeValues = array('foo' => 'bar');

		$this->assertTrue($this->dynamicRoutPart->resolve($routeValues));

		$routeValues = array();
		$this->assertFalse($this->dynamicRoutPart->resolve($routeValues));
		$this->assertNull($this->dynamicRoutPart->getValue(), 'Dynamic Route Part value should be NULL when call to resolve() was not successful.');
	}

}
?>
