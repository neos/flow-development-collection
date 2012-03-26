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
 * Testcase for the MVC Web Routing DynamicRoutePart Class
 *
 */
class DynamicRoutePartTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Mvc\Routing\DynamicRoutePart
	 */
	protected $dynamicRoutPart;

	/**
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $mockPersistenceManager;

	public function setUp() {
		$this->dynamicRoutPart = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Routing\DynamicRoutePart', array('dummy'));

		$this->mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$this->dynamicRoutPart->_set('persistenceManager', $this->mockPersistenceManager);
	}

	/*                                                                        *
	 * URI matching                                                           *
	 *                                                                        */

	/**
	 * @test
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
	 */
	public function dynamicRoutePartDoesNotMatchEmptyRequestPathEvenIfDefaultValueIsSet() {
		$this->dynamicRoutPart->setName('foo');
		$this->dynamicRoutPart->setDefaultValue('bar');

		$routePath = '';
		$this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if $routePath is empty.');
	}

	/**
	 * @test
	 */
	public function dynamicRoutePartDoesNotMatchIfNameIsNotSet() {
		$routePath = 'foo';

		$this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if name is not set.');
	}


	/**
	 * @test
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
	 */
	public function dynamicRouteDoesNotMatchRequestPathWithMoreThanOneSegmentIfSplitStringIsNotSet() {
		$this->dynamicRoutPart->setName('foo');

		$routePath = 'foo/bar';

		$this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if request Path has more than one segment and no split string is set.');
	}

	/**
	 * @test
	 */
	public function dynamicRouteDoesNotMatchRequestPathWithMoreThanOneSegmentIfSplitStringIsNotFound() {
		$this->dynamicRoutPart->setName('foo');
		$this->dynamicRoutPart->setSplitString('not-existing');

		$routePath = 'foo/bar';

		$this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if request Path has more than one segment and does not contain split string.');
	}

	/**
	 * @test
	 */
	public function dynamicRouteMatchesRequestPathWithOnlyOneSegmentIfSplitStringIsNotSet() {
		$this->dynamicRoutPart->setName('foo');

		$routePath = 'bar';

		$this->assertTrue($this->dynamicRoutPart->match($routePath));
		$this->assertEquals('bar', $this->dynamicRoutPart->getValue(), 'Dynamic Route Part should match if request Path has only one segment and no split string is set.');
	}

	/**
	 * @test
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
	 */
	public function dynamicRoutePartDoesNotMatchIfSplitStringIsAtFirstPosition() {
		$this->dynamicRoutPart->setName('foo');
		$this->dynamicRoutPart->setSplitString('-');

		$routePath = '-foo/bar';

		$this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if split string is first character of current request path.');
	}

	/**
	 * @test
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
	 */
	public function dynamicRoutePartDoesNotResolveIfNameIsNotSet() {
		$routeValues = array('foo' => 'bar');

		$this->assertFalse($this->dynamicRoutPart->resolve($routeValues), 'Dynamic Route Part should not resolve if name is not set.');
	}

	/**
	 * @test
	 */
	public function dynamicRoutePartResolvesSimpleValueArray() {
		$this->dynamicRoutPart->setName('foo');
		$routeValues = array('foo' => 'bar');

		$this->assertTrue($this->dynamicRoutPart->resolve($routeValues));
		$this->assertEquals('bar', $this->dynamicRoutPart->getValue(), 'Dynamic Route Part should resolve if an element with the same name exists in $routeValues.');
	}

	/**
	 * @test
	 */
	public function dynamicRoutePartDoesNotResolveEmptyArray() {
		$this->dynamicRoutPart->setName('foo');
		$routeValues = array();

		$this->assertFalse($this->dynamicRoutPart->resolve($routeValues), 'Dynamic Route Part should not resolve an empty $routeValues-array.');
	}

	/**
	 * @test
	 */
	public function dynamicRoutePartDoesNotResolveEmptyArrayEvenIfDefaultValueIsSet() {
		$this->dynamicRoutPart->setName('foo');
		$this->dynamicRoutPart->setDefaultValue('defaultValue');
		$routeValues = array();

		$this->assertFalse($this->dynamicRoutPart->resolve($routeValues), 'Dynamic Route Part should not resolve an empty $routeValues-array even if default Value is set.');
	}

	/**
	 * @test
	 */
	public function dynamicRoutePartLowerCasesValueWhenCallingResolveByDefault() {
		$this->dynamicRoutPart->setName('Foo');
		$routeValues = array('Foo' => 'Bar');

		$this->assertTrue($this->dynamicRoutPart->resolve($routeValues));
		$this->assertEquals('bar', $this->dynamicRoutPart->getValue(), 'By default Dynamic Route Part should lowercase route values.');
	}

	/**
	 * @test
	 */
	public function dynamicRoutePartDoesNotChangeCaseOfValueIfLowerCaseIsFale() {
		$this->dynamicRoutPart->setName('Foo');
		$this->dynamicRoutPart->setLowerCase(FALSE);
		$routeValues = array('Foo' => 'Bar');

		$this->assertTrue($this->dynamicRoutPart->resolve($routeValues));
		$this->assertEquals('Bar', $this->dynamicRoutPart->getValue(), 'Dynamic Route Part should not change the case of the value if lowerCase is false.');
	}

	/**
	 * @test
	 */
	public function resolveReturnsFalseIfNoCorrespondingValueIsGiven() {
		$this->dynamicRoutPart->setName('foo');
		$routeValues = array('notFoo' => 'bar');

		$this->assertFalse($this->dynamicRoutPart->resolve($routeValues), 'Dynamic Route Part should not resolve if no element with the same name exists in $routeValues and no default value is set.');
	}

	/**
	 * @test
	 */
	public function resolveUnsetsCurrentRouteValueOnSuccessfulResolve() {
		$this->dynamicRoutPart->setName('foo');
		$routeValues = array('foo' => 'bar', 'differentString' => 'value2');

		$this->assertTrue($this->dynamicRoutPart->resolve($routeValues));
		$this->assertEquals(array('differentString' => 'value2'), $routeValues, 'Dynamic Route Part should unset matching element from $routeValues on successful resolve.');
	}

	/**
	 * @test
	 */
	public function resolveRecursivelyUnsetsCurrentRouteValueOnSuccessfulResolve() {
		$this->dynamicRoutPart->setName('foo.bar.baz');
		$routeValues = array('foo' => array('bar' => array('baz' => 'should be removed', 'otherKey' => 'should stay')), 'differentString' => 'value2');

		$this->assertTrue($this->dynamicRoutPart->resolve($routeValues));
		$this->assertEquals(array('foo' => array('bar' => array('otherKey' => 'should stay')), 'differentString' => 'value2'), $routeValues);
	}

	/**
	 * @test
	 */
	public function resolveDoesNotChangeRouteValuesOnUnsuccessfulResolve() {
		$this->dynamicRoutPart->setName('foo');
		$routeValues = array('differentString' => 'bar');

		$this->assertFalse($this->dynamicRoutPart->resolve($routeValues));
		$this->assertEquals(array('differentString' => 'bar'), $routeValues, 'Dynamic Route Part should not change $routeValues on unsuccessful resolve.');
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsTrueAndSetTheValueToTheLowerCasedIdentifierIfTheValueToBeResolvedIsAnObject() {
		$object = new \stdClass();
		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue('TheIdentifier'));
		$this->assertTrue($this->dynamicRoutPart->_call('resolveValue', $object));
		$this->assertSame('theidentifier', $this->dynamicRoutPart->getValue());
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsTrueAndSetTheValueToTheCorrectylCasedIdentifierIfTheValueToBeResolvedIsAnObjectAndLowerCaseIsFalse() {
		$object = new \stdClass();
		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue('TheIdentifier'));
		$this->dynamicRoutPart->setLowerCase(FALSE);
		$this->assertTrue($this->dynamicRoutPart->_call('resolveValue', $object));
		$this->assertSame('TheIdentifier', $this->dynamicRoutPart->getValue());
	}


	/**
	 * Objects that are unknown to the persistence manager cannot be resolved by the standard DynamicRoutePart handler.
	 *
	 * @test
	 */
	public function resolveValueReturnsFalseIfTheValueToBeResolvedIsAnObjectThatIsUnknownToThePersistenceManager() {
		$object = new \stdClass();
		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue(NULL));
		$this->assertFalse($this->dynamicRoutPart->_call('resolveValue', $object));
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfTheValueToBeResolvedIsAnObjectWithAnIdentifierThatIsNoString() {
		$object = new \stdClass();
		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue(array('foo', 'bar')));
		$this->assertFalse($this->dynamicRoutPart->_call('resolveValue', $object));
	}

	/**
	 * @test
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
