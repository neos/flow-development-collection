<?php
namespace TYPO3\FLOW3\Tests\Unit\Mvc\Web;

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
		$this->mockParentRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$this->subRequest = new \TYPO3\FLOW3\Mvc\Web\SubRequest($this->mockParentRequest);
	}

	/**
	 * @test
	 */
	public function constructorSetsParentRequest() {
		$this->assertSame($this->mockParentRequest, $this->subRequest->getParentRequest());
	}

	/**
	 * @test
	 */
	public function argumentNamespaceDefaultsToAnEmptyString() {
		$this->assertSame('', $this->subRequest->getArgumentNamespace());
	}

	/**
	 * @test
	 */
	public function argumentNamespaceCanBeSpecified() {
		$this->subRequest->setArgumentNamespace('someArgumentNamespace');
		$this->assertSame('someArgumentNamespace', $this->subRequest->getArgumentNamespace());
	}

	/**
	 * @test
	 */
	public function setRequestUriSetsParentRequestUri() {
		$mockUri = $this->getMock('TYPO3\FLOW3\Http\Uri', array(), array(), '', FALSE);
		$this->mockParentRequest->expects($this->once())->method('setRequestUri')->with($mockUri);
		$this->subRequest->setRequestUri($mockUri);
	}

	/**
	 * @test
	 */
	public function getRequestUriReturnsParentRequestUri() {
		$mockUri = $this->getMock('TYPO3\FLOW3\Http\Uri', array(), array(), '', FALSE);
		$this->mockParentRequest->expects($this->once())->method('getRequestUri')->will($this->returnValue($mockUri));
		$this->assertSame($mockUri, $this->subRequest->getRequestUri());
	}

	/**
	 * @test
	 */
	public function setBaseUriSetsParentRequestBaseUri() {
		$mockUri = $this->getMock('TYPO3\FLOW3\Http\Uri', array(), array(), '', FALSE);
		$this->mockParentRequest->expects($this->once())->method('setBaseUri')->with($mockUri);
		$this->subRequest->setBaseUri($mockUri);
	}

	/**
	 * @test
	 */
	public function getBaseUriReturnsParentRequestBaseUri() {
		$mockUri = $this->getMock('TYPO3\FLOW3\Http\Uri', array(), array(), '', FALSE);
		$this->mockParentRequest->expects($this->once())->method('getBaseUri')->will($this->returnValue($mockUri));
		$this->assertSame($mockUri, $this->subRequest->getBaseUri());
	}

	/**
	 * @test
	 */
	public function setMethodSetsParentRequestMethod() {
		$this->mockParentRequest->expects($this->once())->method('setMethod')->with('SomeMethod');
		$this->subRequest->setMethod('SomeMethod');
	}

	/**
	 * @test
	 */
	public function getMethodReturnsParentRequestMethod() {
		$this->mockParentRequest->expects($this->once())->method('getMethod')->will($this->returnValue('SomeMethod'));
		$this->assertSame('SomeMethod', $this->subRequest->getMethod());
	}

	/**
	 * @test
	 */
	public function getRoutePathReturnsParentRequestRoutePath() {
		$this->mockParentRequest->expects($this->once())->method('getRoutePath')->will($this->returnValue('SomeRoutePath'));
		$this->assertSame('SomeRoutePath', $this->subRequest->getRoutePath());
	}

	/**
	 * @test
	 */
	public function getRootRequestReturnsTopMostParentRequest() {
		$mockRootRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$parentParentRequest = new \TYPO3\FLOW3\Mvc\Web\SubRequest($mockRootRequest);
		$parentRequest = new \TYPO3\FLOW3\Mvc\Web\SubRequest($parentParentRequest);
		$subRequest = new \TYPO3\FLOW3\Mvc\Web\SubRequest($parentRequest);

		$this->assertSame($mockRootRequest, $subRequest->getRootRequest());
	}

}
?>