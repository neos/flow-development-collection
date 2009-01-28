<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authorization;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FilterFirewallTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function configuredFiltersAreCreatedCorrectly() {
		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['security']['firewall']['rejectAll'] = FALSE;
		$settings['security']['firewall']['filters'] = array(
			array(
				'patternType' => 'URL',
				'patternValue' => '/some/url/.*',
				'interceptor' => 'AccessGrant'
			),
			array(
				'patternType' => 'F3\TestPackage\TestRequestPattern',
				'patternValue' => '/some/url/blocked.*',
				'interceptor' => 'F3\TestPackage\TestSecurityInterceptor'
			)
		);

		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$firewall = new \F3\FLOW3\Security\Authorization\FilterFirewall($mockConfigurationManager, $this->objectManager, new \F3\FLOW3\Security\RequestPatternResolver($this->objectManager), new \F3\FLOW3\Security\Authorization\InterceptorResolver($this->objectManager));
		$filters = $firewall->getFilters();

		$this->assertType('F3\FLOW3\Security\Authorization\RequestFilter', $filters[0]);
		$this->assertType('F3\FLOW3\Security\RequestPattern\URL', $filters[0]->getRequestPattern());
		$this->assertEquals('/some/url/.*', $filters[0]->getRequestPattern()->getPattern());
		$this->assertType('F3\FLOW3\Security\Authorization\Interceptor\AccessGrant', $filters[0]->getSecurityInterceptor());

		$this->assertType('F3\FLOW3\Security\Authorization\RequestFilter', $filters[1]);
		$this->assertType('F3\TestPackage\TestRequestPattern', $filters[1]->getRequestPattern());
		$this->assertEquals('/some/url/blocked.*', $filters[1]->getRequestPattern()->getPattern());
		$this->assertType('F3\TestPackage\TestSecurityInterceptor', $filters[1]->getSecurityInterceptor());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Security\Exception\AccessDenied
	 */
	public function configuredFiltersAreCalledAndTheirInterceptorsInvoked() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request');
		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['security']['firewall']['rejectAll'] = FALSE;
		$settings['security']['firewall']['filters'] = array(
			array(
				'patternType' => 'F3\TestPackage\TestRequestPattern',
				'patternValue' => '.*',
				'interceptor' => 'AccessDeny'
			),
		);

		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$firewall = new \F3\FLOW3\Security\Authorization\FilterFirewall($mockConfigurationManager, $this->objectManager, new \F3\FLOW3\Security\RequestPatternResolver($this->objectManager), new \F3\FLOW3\Security\Authorization\InterceptorResolver($this->objectManager));

		$firewall->blockIllegalRequests($mockRequest);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Security\Exception\AccessDenied
	 */
	public function ifRejectAllIsSetAndNoFilterExplicitlyAllowsTheRequestAPermissionDeniedExceptionIsThrown() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Request');
		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['security']['firewall']['rejectAll'] = TRUE;
		$settings['security']['firewall']['filters'] = array(
			array(
				'patternType' => 'URL',
				'patternValue' => '/some/url/.*',
				'interceptor' => 'F3\TestPackage\TestSecurityInterceptor'
			),
		);

		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));

		$firewall = new \F3\FLOW3\Security\Authorization\FilterFirewall($mockConfigurationManager, $this->objectManager, new \F3\FLOW3\Security\RequestPatternResolver($this->objectManager), new \F3\FLOW3\Security\Authorization\InterceptorResolver($this->objectManager));

		$firewall->blockIllegalRequests($mockRequest);
	}
}
?>