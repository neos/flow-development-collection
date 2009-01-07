<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security;

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
 * Testcase for the security context
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class ContextTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAuthenticationTokensReturnsOnlyTokensActiveForThisRequest() {
		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['security']['authentication']['authenticateAllTokens'] = FALSE;
		$mockConfigurationManager->expects($this->any())->method('getSettings')->will($this->returnValue($settings));
		$request = $this->getMock('F3\FLOW3\MVC\Request');

		$matchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), 'matchingRequestPattern');
		$matchingRequestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(TRUE));
		$matchingRequestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(TRUE));

		$notMatchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), 'notMatchingRequestPattern');
		$notMatchingRequestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(TRUE));
		$notMatchingRequestPattern->expects($this->once())->method('matchRequest')->will($this->returnValue(FALSE));

		$abstainingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), 'abstainingRequestPattern');
		$abstainingRequestPattern->expects($this->once())->method('canMatch')->will($this->returnValue(FALSE));
		$abstainingRequestPattern->expects($this->never())->method('matchRequest');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken1');
		$token1->expects($this->once())->method('hasRequestPattern')->will($this->returnValue(TRUE));
		$token1->expects($this->once())->method('getRequestPattern')->will($this->returnValue($matchingRequestPattern));

		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken2');
		$token2->expects($this->once())->method('hasRequestPattern')->will($this->returnValue(FALSE));
		$token2->expects($this->never())->method('getRequestPattern');

		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken3');
		$token3->expects($this->once())->method('hasRequestPattern')->will($this->returnValue(TRUE));
		$token3->expects($this->once())->method('getRequestPattern')->will($this->returnValue($notMatchingRequestPattern));

		$token4 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken4');
		$token4->expects($this->once())->method('hasRequestPattern')->will($this->returnValue(TRUE));
		$token4->expects($this->once())->method('getRequestPattern')->will($this->returnValue($abstainingRequestPattern));

		$securityContext = new \F3\FLOW3\Security\Context($mockConfigurationManager);
		$securityContext->setAuthenticationTokens(array($token1, $token2, $token3, $token4));
		$securityContext->setRequest($request);

		$this->assertEquals(array($token1, $token2), $securityContext->getAuthenticationTokens());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateAllTokensIsSetCorrectlyFromConfiguration() {
		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['security']['authentication']['authenticateAllTokens'] = TRUE;

		$mockConfigurationManager->expects($this->once())->method('getSettings')->will($this->returnValue($settings));
		$securityContext = new \F3\FLOW3\Security\Context($mockConfigurationManager);

		$this->assertTrue($securityContext->authenticateAllTokens());

	}
}
?>