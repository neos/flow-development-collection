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
 * @version $Id:$
 */

/**
 * Testcase for the filter firewall
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authorization_FilterFirewallTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function configuredFiltersAreCreatedCorrectly() {
		$mockConfigurationManager = $this->getMock('F3_FLOW3_Configuration_Manager', array(), array(), '', FALSE);
		$settings = new F3_FLOW3_Configuration_Container();
		$settings->security->firewall->rejectAll = FALSE;
		$settings->security->firewall->filters = array(
			array(
				'patternType' => 'URL',
				'patternValue' => '/some/url/.*',
				'interceptor' => 'AccessGrant'
			),
			array(
				'patternType' => 'F3_TestPackage_TestRequestPattern',
				'patternValue' => '/some/url/blocked.*',
				'interceptor' => 'F3_TestPackage_TestSecurityInterceptor'
			)
		);

		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$firewall = new F3_FLOW3_Security_Authorization_FilterFirewall($mockConfigurationManager, $this->componentFactory, new F3_FLOW3_Security_RequestPatternResolver($this->componentManager), new F3_FLOW3_Security_Authorization_InterceptorResolver($this->componentManager));
		$filters = $firewall->getFilters();

		$this->assertType('F3_FLOW3_Security_Authorization_RequestFilter', $filters[0]);
		$this->assertType('F3_FLOW3_Security_RequestPattern_URL', $filters[0]->getRequestPattern());
		$this->assertEquals('/some/url/.*', $filters[0]->getRequestPattern()->getPattern());
		$this->assertType('F3_FLOW3_Security_Authorization_Interceptor_AccessGrant', $filters[0]->getSecurityInterceptor());

		$this->assertType('F3_FLOW3_Security_Authorization_RequestFilter', $filters[1]);
		$this->assertType('F3_TestPackage_TestRequestPattern', $filters[1]->getRequestPattern());
		$this->assertEquals('/some/url/blocked.*', $filters[1]->getRequestPattern()->getPattern());
		$this->assertType('F3_TestPackage_TestSecurityInterceptor', $filters[1]->getSecurityInterceptor());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function configuredFiltersAreCalledAndTheirInterceptorsInvoked() {
		$mockRequest = $this->getMock('F3_FLOW3_MVC_Web_Request');
		$mockConfigurationManager = $this->getMock('F3_FLOW3_Configuration_Manager', array(), array(), '', FALSE);
		$settings = new F3_FLOW3_Configuration_Container();
		$settings->security->firewall->rejectAll = FALSE;
		$settings->security->firewall->filters = array(
			array(
				'patternType' => 'F3_TestPackage_TestRequestPattern',
				'patternValue' => '.*',
				'interceptor' => 'AccessDeny'
			),
		);

		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$firewall = new F3_FLOW3_Security_Authorization_FilterFirewall($mockConfigurationManager, $this->componentFactory, new F3_FLOW3_Security_RequestPatternResolver($this->componentManager), new F3_FLOW3_Security_Authorization_InterceptorResolver($this->componentManager));

		try {
			$firewall->blockIllegalRequests($mockRequest);
			$this->fail('The AccessDenyInterceptor has not been called.');
		} catch (F3_FLOW3_Security_Exception_AccessDenied $exception) {

		}
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifRejectAllIsSetAndNoFilterExplicitlyAllowsTheRequestAPermissionDeniedExceptionIsThrown() {
		$mockRequest = $this->getMock('F3_FLOW3_MVC_Request');
		$mockConfigurationManager = $this->getMock('F3_FLOW3_Configuration_Manager', array(), array(), '', FALSE);
		$settings = new F3_FLOW3_Configuration_Container();
		$settings->security->firewall->rejectAll = TRUE;
		$settings->security->firewall->filters = array(
			array(
				'patternType' => 'URL',
				'patternValue' => '/some/url/.*',
				'interceptor' => 'F3_TestPackage_TestSecurityInterceptor'
			),
		);

		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$firewall = new F3_FLOW3_Security_Authorization_FilterFirewall($mockConfigurationManager, $this->componentFactory, new F3_FLOW3_Security_RequestPatternResolver($this->componentManager), new F3_FLOW3_Security_Authorization_InterceptorResolver($this->componentManager));

		try {
			$firewall->blockIllegalRequests($mockRequest);
			$this->fail('The AccessDenyInterceptor has not been called.');
		} catch (F3_FLOW3_Security_Exception_AccessDenied $exception) {

		}
	}
}
?>