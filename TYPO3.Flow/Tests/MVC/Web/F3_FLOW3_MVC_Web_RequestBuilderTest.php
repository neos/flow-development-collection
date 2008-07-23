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
 * @subpackage Tests
 * @version $Id:F3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the MVC Web Request Builder
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_Web_RequestBuilderTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildReturnsAWebRequestObject() {
		$mockRequestURI = $this->getMock('F3_FLOW3_Property_DataType_URI', array(), array(), '', FALSE);
		$mockEnvironment = $this->getMock('F3_FLOW3_Utility_Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getRequestURI')->will($this->returnValue($mockRequestURI));

		$mockRequest = $this->getMock('F3_FLOW3_MVC_Web_Request', array('injectEnvironment', 'setRequestURI'), array(), '', FALSE);

		$mockComponentFactory = $this->getMock('F3_FLOW3_Component_FactoryInterface');
		$mockComponentFactory->expects($this->once())->method('getComponent')->will($this->returnValue($mockRequest));

		$mockRouter = $this->getMock('F3_FLOW3_MVC_Web_Routing_RouterInterface', array('route'));

		$builder = new F3_FLOW3_MVC_Web_RequestBuilder($mockComponentFactory, $mockEnvironment, $mockRouter);
		$returnedObject = $builder->build();

		$this->assertSame($mockRequest, $returnedObject);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildSetsTheRequestURIInTheRequestObject() {
		$mockRequestURI = $this->getMock('F3_FLOW3_Property_DataType_URI', array(), array(), '', FALSE);
		$mockEnvironment = $this->getMock('F3_FLOW3_Utility_Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getRequestURI')->will($this->returnValue($mockRequestURI));

		$mockRequest = $this->getMock('F3_FLOW3_MVC_Web_Request', array('injectEnvironment', 'setRequestURI'), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('injectEnvironment')->with($this->equalTo($mockEnvironment));

		$mockComponentFactory = $this->getMock('F3_FLOW3_Component_FactoryInterface');
		$mockComponentFactory->expects($this->once())->method('getComponent')->will($this->returnValue($mockRequest));

		$mockRouter = $this->getMock('F3_FLOW3_MVC_Web_Routing_RouterInterface', array('route'));

		$builder = new F3_FLOW3_MVC_Web_RequestBuilder($mockComponentFactory, $mockEnvironment, $mockRouter);
		$builder->build();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildInvokesTheRouteMethodOfTheRouter() {
		$mockRequestURI = $this->getMock('F3_FLOW3_Property_DataType_URI', array(), array(), '', FALSE);
		$mockEnvironment = $this->getMock('F3_FLOW3_Utility_Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->any())->method('getRequestURI')->will($this->returnValue($mockRequestURI));

		$mockRequest = $this->getMock('F3_FLOW3_MVC_Web_Request', array('injectEnvironment', 'setRequestURI'), array(), '', FALSE);

		$mockComponentFactory = $this->getMock('F3_FLOW3_Component_FactoryInterface');
		$mockComponentFactory->expects($this->once())->method('getComponent')->will($this->returnValue($mockRequest));

		$mockRouter = $this->getMock('F3_FLOW3_MVC_Web_Routing_RouterInterface', array('route'));
		$mockRouter->expects($this->once())->method('route');

		$builder = new F3_FLOW3_MVC_Web_RequestBuilder($mockComponentFactory, $mockEnvironment, $mockRouter);
		$builder->build();
	}
}
?>