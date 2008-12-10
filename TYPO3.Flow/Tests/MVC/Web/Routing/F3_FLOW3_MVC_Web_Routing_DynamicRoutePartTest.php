<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web\Routing;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the MVC Web Routing DynamicRoutePart Class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class DynamicRoutePartTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartIsPrototype() {
		$routePart1 = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\DynamicRoutePart');
		$routePart2 = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\DynamicRoutePart');
		$this->assertNotSame($routePart1, $routePart2, 'Obviously the Dynamic Route Part is not prototype!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchIfUriSegmentIsEmptyOrNullAndNoDefaultValueIsSet() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');

		$uriSegments = array();
		$this->assertFalse($routePart->match($uriSegments), 'Dynamic Route Part should not match if uriSegments array is empty and no default value is set.');

		$uriSegments = array(NULL, 'foo');
		$this->assertFalse($routePart->match($uriSegments), 'Dynamic Route Part should never match if uriSegment is NULL.');

		$uriSegments = array('', 'foo');
		$this->assertFalse($routePart->match($uriSegments), 'Dynamic Route Part should never match if current uriSegment is empty and no default value is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartMatchesIfDefaultValueIsSet() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routePart->setDefaultValue('bar');

		$uriSegments = array();
		$this->assertTrue($routePart->match($uriSegments), 'Dynamic Route Part should match if uriSegments array is empty and a default value is set.');

		$uriSegments = array('', 'foo');
		$this->assertTrue($routePart->match($uriSegments), 'Dynamic Route Part should match if current uriSegment is empty and a default value is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchIfNameIsNotSet() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$uriSegments = array('foo', 'bar');
		$routePart->setDefaultValue('foo');

		$this->assertFalse($routePart->match($uriSegments), 'Dynamic Route Part should not match if name is not set.');
	}


	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valueMatchesFirstUriSegmentAfterSuccessfulMatch() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routePart->setDefaultValue('bar');

		$uriSegments = array('firstSegment', 'secondSegment');
		$routePart->match($uriSegments);

		$this->assertEquals('firstSegment', $routePart->getValue(), 'value of Dynamic Route Part should be equal to first uriSegment after successful match.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valueIsNullAfterUnsuccessfulMatch() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');

		$uriSegments = array('foo', 'bar');
		$routePart->match($uriSegments);

		$uriSegments = array('', 'foo');
		$routePart->match($uriSegments);
		$this->assertNull($routePart->getValue(), 'Dynamic Route Part value should be NULL after unsuccessful match.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriSegmentsAreShortenedByOneSegmentAfterSuccessfulMatch() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('bar');
		$uriSegments = array('bar', 'foo', 'test');
		$routePart->match($uriSegments);

		$this->assertSame(array('foo', 'test'), $uriSegments, 'Dynamic Route Part should shorten uriSegments array by one entry on successful match.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartMatchesIfSplitStringIsFound() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$mockRoutePart = $this->getMock('F3\FLOW3\MVC\Web\Routing\AbstractRoutePart');
		$mockRoutePart->expects($this->once())->method('getName')->will($this->returnValue('-'));
		$mockUriPatternSegmentCollection = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection');
		$mockUriPatternSegmentCollection->expects($this->once())->method('getNextRoutePartInCurrentUriPatternSegment')->will($this->returnValue($mockRoutePart));
		$routePart->setUriPatternSegments($mockUriPatternSegmentCollection);
		$uriSegments = array('foo-bar', 'test');

		$this->assertTrue($routePart->match($uriSegments), 'Dynamic Route Part should match if current uriSegment contains splitString.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchIfSplitStringIsNotFound() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$mockRoutePart = $this->getMock('F3\FLOW3\MVC\Web\Routing\AbstractRoutePart');
		$mockRoutePart->expects($this->once())->method('getName')->will($this->returnValue('-'));
		$mockUriPatternSegmentCollection = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection');
		$mockUriPatternSegmentCollection->expects($this->once())->method('getNextRoutePartInCurrentUriPatternSegment')->will($this->returnValue($mockRoutePart));
		$routePart->setUriPatternSegments($mockUriPatternSegmentCollection);
		$uriSegments = array('foo', 'test');

		$this->assertFalse($routePart->match($uriSegments), 'Dynamic Route Part should not match if current uriSegment does not contain splitString.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartShortensCurrentUriSegmentAfterSuccessfulMatch() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$mockRoutePart = $this->getMock('F3\FLOW3\MVC\Web\Routing\AbstractRoutePart');
		$mockRoutePart->expects($this->once())->method('getName')->will($this->returnValue('-'));
		$mockUriPatternSegmentCollection = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection');
		$mockUriPatternSegmentCollection->expects($this->once())->method('getNextRoutePartInCurrentUriPatternSegment')->will($this->returnValue($mockRoutePart));
		$routePart->setUriPatternSegments($mockUriPatternSegmentCollection);
		$uriSegments = array('foo-bar', 'test');
		$this->assertTrue($routePart->match($uriSegments));
		
		$this->assertSame(array('-bar', 'test'), $uriSegments,  'Dynamic Route Part should cut off first part of matching string until splitString.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchIfSplitStringIsAtFirstPosition() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$mockRoutePart = $this->getMock('F3\FLOW3\MVC\Web\Routing\AbstractRoutePart');
		$mockRoutePart->expects($this->once())->method('getName')->will($this->returnValue('-'));
		$mockUriPatternSegmentCollection = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection');
		$mockUriPatternSegmentCollection->expects($this->once())->method('getNextRoutePartInCurrentUriPatternSegment')->will($this->returnValue($mockRoutePart));
		$routePart->setUriPatternSegments($mockUriPatternSegmentCollection);
		$uriSegments = array('-foo', 'bar');

		$this->assertFalse($routePart->match($uriSegments), 'Dynamic Route Part should not match if splitString is first character of current uriSegment.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchIfSplitStringContainsMoreCharactersThatAreNotInUriSegments() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$mockRoutePart = $this->getMock('F3\FLOW3\MVC\Web\Routing\AbstractRoutePart');
		$mockRoutePart->expects($this->once())->method('getName')->will($this->returnValue('_-_'));
		$mockUriPatternSegmentCollection = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection');
		$mockUriPatternSegmentCollection->expects($this->once())->method('getNextRoutePartInCurrentUriPatternSegment')->will($this->returnValue($mockRoutePart));
		$routePart->setUriPatternSegments($mockUriPatternSegmentCollection);

		$uriSegments = array('foo-bar', 'bar');
		$this->assertFalse($routePart->match($uriSegments), 'Dynamic Route Part with a splitString of "_-_" should not match uriParts separated by "-".');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartMatchesIfSplitStringContainsMoreCharactersThatAreNotInUriSegments() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$mockRoutePart = $this->getMock('F3\FLOW3\MVC\Web\Routing\AbstractRoutePart');
		$mockRoutePart->expects($this->once())->method('getName')->will($this->returnValue('_-_'));
		$mockUriPatternSegmentCollection = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection');
		$mockUriPatternSegmentCollection->expects($this->once())->method('getNextRoutePartInCurrentUriPatternSegment')->will($this->returnValue($mockRoutePart));
		$routePart->setUriPatternSegments($mockUriPatternSegmentCollection);

		$uriSegments = array('foo_-_bar', 'bar');
		$this->assertTrue($routePart->match($uriSegments), 'Dynamic Route Part with a splitString of "_-_" should match uriParts separated by "_-_".');
	}

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
	public function dynamicRoutePartResolvesEmptyArrayIfDefaultValueIsSet() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routePart->setDefaultValue('defaultValue');
		$routeValues = array();
		
		$this->assertTrue($routePart->resolve($routeValues));
		$this->assertEquals('defaultValue', $routePart->getValue(), 'Dynamic Route Part should resolve if a default value is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveReturnsFalseIfNoCorrespondingValueIsGiven() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routeValues = array('differentString' => 'bar');

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
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routePartValueIsNullAfterUnsuccessfulResolve() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\DynamicRoutePart();
		$routePart->setName('foo');
		$routePart->setDefaultValue('defaultValue');
		$routeValues = array();
		
		$this->assertTrue($routePart->resolve($routeValues));
		
		$routePart->setDefaultValue(NULL);
		$this->assertFalse($routePart->resolve($routeValues));
		$this->assertNull($routePart->getValue(), 'Dynamic Route Part value should be NULL when call to resolve() was not successful.');
	}
}
?>
