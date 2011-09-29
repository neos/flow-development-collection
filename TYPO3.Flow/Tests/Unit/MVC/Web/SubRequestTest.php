<?php
namespace TYPO3\FLOW3\Tests\Unit\MVC\Web;

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
 * Testcase for the MVC Web SubRequest class
 *
 */
class SubRequestTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	protected $subRequest;

	protected $mockParentRequest;

	public function setUp() {
		$this->mockParentRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request');
		$this->subRequest = new \TYPO3\FLOW3\MVC\Web\SubRequest($this->mockParentRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorSetsParentRequest() {
		$this->assertSame($this->mockParentRequest, $this->subRequest->getParentRequest());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function argumentNamespaceDefaultsToAnEmptyString() {
		$this->assertSame('', $this->subRequest->getArgumentNamespace());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function argumentNamespaceCanBeSpecified() {
		$this->subRequest->setArgumentNamespace('someArgumentNamespace');
		$this->assertSame('someArgumentNamespace', $this->subRequest->getArgumentNamespace());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setRequestUriSetsParentRequestUri() {
		$mockUri = $this->getMock('TYPO3\FLOW3\Property\DataType\Uri', array(), array(), '', FALSE);
		$this->mockParentRequest->expects($this->once())->method('setRequestUri')->with($mockUri);
		$this->subRequest->setRequestUri($mockUri);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getRequestUriReturnsParentRequestUri() {
		$mockUri = $this->getMock('TYPO3\FLOW3\Property\DataType\Uri', array(), array(), '', FALSE);
		$this->mockParentRequest->expects($this->once())->method('getRequestUri')->will($this->returnValue($mockUri));
		$this->assertSame($mockUri, $this->subRequest->getRequestUri());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setBaseUriSetsParentRequestBaseUri() {
		$mockUri = $this->getMock('TYPO3\FLOW3\Property\DataType\Uri', array(), array(), '', FALSE);
		$this->mockParentRequest->expects($this->once())->method('setBaseUri')->with($mockUri);
		$this->subRequest->setBaseUri($mockUri);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getBaseUriReturnsParentRequestBaseUri() {
		$mockUri = $this->getMock('TYPO3\FLOW3\Property\DataType\Uri', array(), array(), '', FALSE);
		$this->mockParentRequest->expects($this->once())->method('getBaseUri')->will($this->returnValue($mockUri));
		$this->assertSame($mockUri, $this->subRequest->getBaseUri());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setMethodSetsParentRequestMethod() {
		$this->mockParentRequest->expects($this->once())->method('setMethod')->with('SomeMethod');
		$this->subRequest->setMethod('SomeMethod');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getMethodReturnsParentRequestMethod() {
		$this->mockParentRequest->expects($this->once())->method('getMethod')->will($this->returnValue('SomeMethod'));
		$this->assertSame('SomeMethod', $this->subRequest->getMethod());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getRoutePathReturnsParentRequestRoutePath() {
		$this->mockParentRequest->expects($this->once())->method('getRoutePath')->will($this->returnValue('SomeRoutePath'));
		$this->assertSame('SomeRoutePath', $this->subRequest->getRoutePath());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getRootRequestReturnsTopMostParentRequest() {
		$mockRootRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request');
		$parentParentRequest = new \TYPO3\FLOW3\MVC\Web\SubRequest($mockRootRequest);
		$parentRequest = new \TYPO3\FLOW3\MVC\Web\SubRequest($parentParentRequest);
		$subRequest = new \TYPO3\FLOW3\MVC\Web\SubRequest($parentRequest);

		$this->assertSame($mockRootRequest, $subRequest->getRootRequest());
	}

}
?>