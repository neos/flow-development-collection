<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::Authorization;

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
 * Testcase for the filter firewall
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class FilterFirewallTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function configuredFiltersAreCreatedCorrectly() {
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['security']['firewall']['rejectAll'] = FALSE;
		$settings['security']['firewall']['filters'] = array(
			array(
				'patternType' => 'URL',
				'patternValue' => '/some/url/.*',
				'interceptor' => 'AccessGrant'
			),
			array(
				'patternType' => 'F3::TestPackage::TestRequestPattern',
				'patternValue' => '/some/url/blocked.*',
				'interceptor' => 'F3::TestPackage::TestSecurityInterceptor'
			)
		);

		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$firewall = new F3::FLOW3::Security::Authorization::FilterFirewall($mockConfigurationManager, $this->objectManager, new F3::FLOW3::Security::RequestPatternResolver($this->objectManager), new F3::FLOW3::Security::Authorization::InterceptorResolver($this->objectManager));
		$filters = $firewall->getFilters();

		$this->assertType('F3::FLOW3::Security::Authorization::RequestFilter', $filters[0]);
		$this->assertType('F3::FLOW3::Security::RequestPattern::URL', $filters[0]->getRequestPattern());
		$this->assertEquals('/some/url/.*', $filters[0]->getRequestPattern()->getPattern());
		$this->assertType('F3::FLOW3::Security::Authorization::Interceptor::AccessGrant', $filters[0]->getSecurityInterceptor());

		$this->assertType('F3::FLOW3::Security::Authorization::RequestFilter', $filters[1]);
		$this->assertType('F3::TestPackage::TestRequestPattern', $filters[1]->getRequestPattern());
		$this->assertEquals('/some/url/blocked.*', $filters[1]->getRequestPattern()->getPattern());
		$this->assertType('F3::TestPackage::TestSecurityInterceptor', $filters[1]->getSecurityInterceptor());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function configuredFiltersAreCalledAndTheirInterceptorsInvoked() {
		$mockRequest = $this->getMock('F3::FLOW3::MVC::Web::Request');
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['security']['firewall']['rejectAll'] = FALSE;
		$settings['security']['firewall']['filters'] = array(
			array(
				'patternType' => 'F3::TestPackage::TestRequestPattern',
				'patternValue' => '.*',
				'interceptor' => 'AccessDeny'
			),
		);


		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$firewall = new F3::FLOW3::Security::Authorization::FilterFirewall($mockConfigurationManager, $this->objectManager, new F3::FLOW3::Security::RequestPatternResolver($this->objectManager), new F3::FLOW3::Security::Authorization::InterceptorResolver($this->objectManager));

		try {
			$firewall->blockIllegalRequests($mockRequest);
			$this->fail('The AccessDenyInterceptor has not been called.');
		} catch (F3::FLOW3::Security::Exception::AccessDenied $exception) {

		}
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function ifRejectAllIsSetAndNoFilterExplicitlyAllowsTheRequestAPermissionDeniedExceptionIsThrown() {
		$mockRequest = $this->getMock('F3::FLOW3::MVC::Request');
		$mockConfigurationManager = $this->getMock('F3::FLOW3::Configuration::Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['security']['firewall']['rejectAll'] = TRUE;
		$settings['security']['firewall']['filters'] = array(
			array(
				'patternType' => 'URL',
				'patternValue' => '/some/url/.*',
				'interceptor' => 'F3::TestPackage::TestSecurityInterceptor'
			),
		);

		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$firewall = new F3::FLOW3::Security::Authorization::FilterFirewall($mockConfigurationManager, $this->objectManager, new F3::FLOW3::Security::RequestPatternResolver($this->objectManager), new F3::FLOW3::Security::Authorization::InterceptorResolver($this->objectManager));

		try {
			$firewall->blockIllegalRequests($mockRequest);
			$this->fail('The AccessDenyInterceptor has not been called.');
		} catch (F3::FLOW3::Security::Exception::AccessDenied $exception) {

		}
	}
}
?>