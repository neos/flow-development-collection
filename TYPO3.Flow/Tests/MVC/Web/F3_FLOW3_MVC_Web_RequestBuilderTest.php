<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC::Web;

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
 * @version $Id:F3::FLOW3::Object::TransientRegistryTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the MVC Web Request Builder
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3::FLOW3::Object::TransientRegistryTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class RequestBuilderTest extends F3::Testing::BaseTestCase {

	/**
	 * The mocked request
	 *
	 * @var F3::FLOW3::MVC::Web::Request
	 */
	protected $mockRequest;

	/**
	 * @var F3::FLOW3::Property::DataType::URI
	 */
	protected $mockRequestURI;

	/**
	 * @var F3::FLOW3::Utility::Environment
	 */
	protected $mockEnvironment;

	/**
	 * @var F3::FLOW3::MVC::Web::Routing::RouterInterface
	 */
	protected $mockRouter;

	/**
	 * @var F3::FLOW3::Configuration::Manager
	 */
	protected $mockConfigurationManager;

	/**
	 * @var F3::FLOW3::MVC::Web::RequestBuilder
	 */
	protected $builder;

	/**
	 * Sets up a request builder for testing
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->mockRequestURI = $this->getMock('F3::FLOW3::Property::DataType::URI', array(), array(), '', FALSE);
		$this->mockEnvironment = $this->getMock('F3::FLOW3::Utility::Environment', array(), array(), '', FALSE);
		$this->mockEnvironment->expects($this->any())->method('getRequestURI')->will($this->returnValue($this->mockRequestURI));

		$this->mockRequest = $this->getMock('F3::FLOW3::MVC::Web::Request', array('injectEnvironment', 'setRequestURI', 'setMethod'), array(), '', FALSE);

		$mockObjectFactory = $this->getMock('F3::FLOW3::Object::FactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')->will($this->returnValue($this->mockRequest));

		$this->mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array('getSpecialConfiguration'), array(), '', FALSE);
		$this->mockConfigurationManager->expects($this->once())->method('getSpecialConfiguration')->will($this->returnValue(array()));

		$this->mockRouter = $this->getMock('F3::FLOW3::MVC::Web::Routing::RouterInterface', array('route', 'setRoutesConfiguration', 'resolve'));

		$this->builder = new F3::FLOW3::MVC::Web::RequestBuilder($mockObjectFactory);
		$this->builder->injectEnvironment($this->mockEnvironment);
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

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildDetectsTheRequestMethodAndSetsItInTheRequestObject() {
		$this->mockEnvironment->expects($this->any())->method('getRequestMethod')->will($this->returnValue('GET'));
		$this->mockRequest->expects($this->once())->method('setMethod')->with($this->equalTo('GET'));
		$this->builder->build();
	}
}
?>