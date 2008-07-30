<?php
declare(ENCODING = 'utf-8');

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
 * @subpackage MVC
 * @version $Id:F3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the MVC Web Request Builder
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_Web_RequestBuilderTest extends F3_Testing_BaseTestCase {

	/**
	 * The mocked request
	 *
	 * @var F3_FLOW3_MVC_Web_Request
	 */
	protected $mockRequest;

	/**
	 * @var F3_FLOW3_Property_DataType_URI
	 */
	protected $mockRequestURI;

	/**
	 * @var F3_FLOW3_MVC_Web_Routing_RouterInterface
	 */
	protected $mockRouter;

	/**
	 * @var F3_FLOW3_Configuration_Manager
	 */
	protected $mockConfigurationManager;

	/**
	 * @var F3_FLOW3_MVC_Web_RequestBuilder
	 */
	protected $builder;

	/**
	 * Sets up a request builder for testing
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->mockRequestURI = $this->getMock('F3_FLOW3_Property_DataType_URI', array(), array(), '', FALSE);
		$mockEnvironment = $this->getMock('F3_FLOW3_Utility_Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getRequestURI')->will($this->returnValue($this->mockRequestURI));

		$this->mockRequest = $this->getMock('F3_FLOW3_MVC_Web_Request', array('injectEnvironment', 'setRequestURI'), array(), '', FALSE);

		$mockComponentFactory = $this->getMock('F3_FLOW3_Component_FactoryInterface');
		$mockComponentFactory->expects($this->once())->method('getComponent')->will($this->returnValue($this->mockRequest));

		$this->mockConfigurationManager = $this->getMock('F3_FLOW3_Configuration_Manager', array('getSpecialConfiguration'), array(), '', FALSE);
		$this->mockConfigurationManager->expects($this->once())->method('getSpecialConfiguration')->will($this->returnValue(new F3_FLOW3_Configuration_Container()));

		$this->mockRouter = $this->getMock('F3_FLOW3_MVC_Web_Routing_RouterInterface', array('route', 'setRoutesConfiguration'));

		$this->builder = new F3_FLOW3_MVC_Web_RequestBuilder($mockComponentFactory);
		$this->builder->injectEnvironment($mockEnvironment);
		$this->builder->injectConfigurationManager($this->mockConfigurationManager);
		$this->builder->injectRouter($this->mockRouter);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildReturnsAWebRequestObject() {
		$this->assertSame($this->mockRequest, $this->builder->build());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildSetsTheRequestURIInTheRequestObject() {
		$this->mockRequest->expects($this->once())->method('setRequestURI')->with($this->equalTo($this->mockRequestURI));
		$this->builder->build();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildInvokesTheRouteMethodOfTheRouter() {
		$this->mockRouter->expects($this->once())->method('route');
		$this->builder->build();
	}
}
?>