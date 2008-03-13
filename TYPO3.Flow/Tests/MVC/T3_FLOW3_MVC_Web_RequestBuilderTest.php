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
 * @version $Id:T3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the MVC Web Request Builder
 *
 * CURRENTLY DISABLED - THESE TESTS DON'T REALLY TEST THE REQUEST BUILDER BUT RATHER THE
 * OTHER CLASSES INVOLVED. NEEDS TO BE REFACTORED.
 *
 * @todo refactor
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:T3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_MVC_Web_RequestBuilderTest extends T3_Testing_BaseTestCase {

	/**
	 * @var T3_FLOW3_Utility_MockEnvironment
	 */
	protected $environment;

	/**
	 * @var T3_FLOW3_MVC_Web_RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		$this->environment = new T3_FLOW3_Utility_MockEnvironment;
		$router = $this->getMock('T3_FLOW3_MVC_Web_Router', array(), array(), '', FALSE);
		$this->requestBuilder = new T3_FLOW3_MVC_Web_RequestBuilder($this->componentManager, $this->environment, $router);
	}

	/**
	 * @test
	 */
	public function thisTestCaseNeedsRefactoring() {

	}

	/**
	 * test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theBuiltControllerNameFollowsTheRequiredScheme() {
		$realRequestURI = $this->environment->getRequestURI();
		$realBaseURI = $this->detectBaseURI($realRequestURI);

		$this->environment->SERVER['REQUEST_URI'] = $realBaseURI->getPath() . 'TestPackage';
		$request = $this->requestBuilder->build();
		var_dump($request);
		$this->assertEquals('T3_TestPackage_Controller_Default', $request->getControllerName(), 'The controller name of the built request object is not as expected.');
	}

	/**
	 * test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function multipleGETArgumentsAreRecognizedCorrectly() {
		$realRequestURI = $this->environment->getRequestURI();
		$realBaseURI = $this->detectBaseURI($realRequestURI);

		$this->environment->SERVER['REQUEST_URI'] = $realBaseURI->getPath() . 'TestPackage?argument1=value1&argument2=value2#anchor';
		$this->environment->POST = array();
		$request = $this->requestBuilder->build();

		$expectedArguments = new ArrayObject(array(
			'argument1' => 'value1',
			'argument2' => 'value2',
		));
		$this->assertEquals($expectedArguments, $request->getArguments(), 'request->getArguments() did not return the expected arguments.');
	}

	/**
	 * test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function singleGETArgumentIsRecognizedCorrectly() {
		$realRequestURI = $this->environment->getRequestURI();
		$realBaseURI = $this->detectBaseURI($realRequestURI);

		$this->environment->SERVER['REQUEST_URI'] = $realBaseURI->getPath() . 'TestPackage?argument1=' . urlencode('valueœåø');
		$this->environment->POST = array();
		$request = $this->requestBuilder->build();

		$expectedArguments = new ArrayObject(array(
			'argument1' => 'valueœåø',
		));
		$this->assertEquals($expectedArguments, $request->getArguments(), 'request->getArguments() did not return the expected arguments.');
	}

	/**
	 * test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function singlePOSTArgumentIsRecognizedCorrectly() {
		$realRequestURI = $this->environment->getRequestURI();
		$realBaseURI = $this->detectBaseURI($realRequestURI);

		$this->environment->SERVER['REQUEST_URI'] = $realBaseURI->getPath() . 'TestPackage';
		$this->environment->POST = array(
			'postargument1' => 'valueœåø',
		);
		$request = $this->requestBuilder->build();

		$expectedArguments = new ArrayObject($this->environment->POST);
		$this->assertEquals($expectedArguments, $request->getArguments(), 'request->getArguments() did not return the expected arguments.');
	}

	/**
	 * test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function GETArgumentsOverridePOSTArguments() {
		$realRequestURI = $this->environment->getRequestURI();
		$realBaseURI = $this->detectBaseURI($realRequestURI);

		$this->environment->SERVER['REQUEST_URI'] = $realBaseURI->getPath() . 'TestPackage?argument1=value1G&argument3=value3G';
		$this->environment->POST = array(
			'argument1' => 'value1P',
			'argument2' => 'value2P',
			'argument3' => 'value3P',
		);
		$request = $this->requestBuilder->build();

		$expectedArguments = new ArrayObject(array(
			'argument1' => 'value1G',
			'argument2' => 'value2P',
			'argument3' => 'value3G',
		));
		$this->assertEquals($expectedArguments, $request->getArguments(), 'request->getArguments() did not return the expected arguments.');
	}

	/**
	 * Tries to detect the base URI of this request and returns it.
	 *
	 * @param  T3_FLOW3_Property_DataType_URI $requestURI: URI of this web request
	 * @return T3_FLOW3_Property_DataType_URI The detected base URI
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function detectBaseURI(T3_FLOW3_Property_DataType_URI $requestURI) {
		$baseURI = clone $requestURI;
		$baseURI->setQuery(NULL);
		$baseURI->setFragment(NULL);

		$requestPathSegments = explode('/', $this->environment->getScriptRequestPathAndName());
		array_pop($requestPathSegments);
		$baseURI->setPath(implode('/', $requestPathSegments) . '/');
		return $baseURI;
	}
}
?>