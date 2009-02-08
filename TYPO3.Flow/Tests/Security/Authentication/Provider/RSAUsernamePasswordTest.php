<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication\Provider;

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
 * @version $Id: F3_FLOW3_Security_Authentication_Provider_UsernamePasswordTest.php 1707 2009-01-07 10:37:30Z k-fish $
 */

/**
 * Testcase for RSA username/password authentication provider
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: F3_FLOW3_Security_Authentication_Provider_UsernamePasswordTest.php 1707 2009-01-07 10:37:30Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class RSAUsernamePasswordTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticatingARSAUsernamePasswordTokenWorks() {
		$mockToken = $this->getMock('F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('encryptedUsername' => 'some fake value', 'encryptedPassword' => 'some other fake value')));
		$mockToken->expects($this->any())->method('getPasswordKeypairUUID')->will($this->returnValue('b2021eba-8084-4955-b2b6-a0a2c9bfad45'));
		$mockToken->expects($this->any())->method('getUsernameKeypairUUID')->will($this->returnValue('b2021eba-8084-4955-b2b6-a0a2c9bfad46'));
		$mockToken->expects($this->once())->method('setAuthenticationStatus')->with(TRUE);

		$mockWalletService = $this->getMock('F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface', array(), array(), '', FALSE);
		$mockWalletService->expects($this->once())->method('decrypt')->will($this->returnValue('admin'));
		$mockWalletService->expects($this->once())->method('checkRSAEncryptedPassword')->will($this->returnValue(TRUE));

		$RSAUsernamePasswordProvider = new \F3\FLOW3\Security\Authentication\Provider\RSAUsernamePassword();
		$RSAUsernamePasswordProvider->injectRSAWalletService($mockWalletService);
		$RSAUsernamePasswordProvider->authenticate($mockToken);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticationFailsWithWrongCredentialsInARSAUsernamePasswordToken() {
		$mockToken = $this->getMock('F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('encryptedUsername' => 'some fake value', 'encryptedPassword' => 'some other fake value')));
		$mockToken->expects($this->any())->method('getPasswordKeypairUUID')->will($this->returnValue('b2021eba-8084-4955-b2b6-a0a2c9bfad45'));
		$mockToken->expects($this->any())->method('getUsernameKeypairUUID')->will($this->returnValue('b2021eba-8084-4955-b2b6-a0a2c9bfad46'));
		$mockToken->expects($this->never())->method('setAuthenticationStatus');

		$mockWalletService = $this->getMock('F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface', array(), array(), '', FALSE);
		$mockWalletService->expects($this->once())->method('decrypt')->will($this->returnValue('admin'));
		$mockWalletService->expects($this->once())->method('checkRSAEncryptedPassword')->will($this->returnValue(FALSE));

		$RSAUsernamePasswordProvider = new \F3\FLOW3\Security\Authentication\Provider\RSAUsernamePassword();
		$RSAUsernamePasswordProvider->injectRSAWalletService($mockWalletService);
		$RSAUsernamePasswordProvider->authenticate($mockToken);
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException \F3\FLOW3\Security\Exception\UnsupportedAuthenticationToken
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticatingAnUnsupportedTokenThrowsAnException() {
		$someNiceToken = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');

		$RSAUsernamePasswordProvider = new \F3\FLOW3\Security\Authentication\Provider\RSAUsernamePassword();

		$RSAUsernamePasswordProvider->authenticate($someNiceToken);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canAuthenticateReturnsTrueOnlyForTheRSAUsernamePasswordToken() {
		$mockRSAUserNamePasswordToken = $this->getMock('F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword', array(), array(), '', FALSE);
		$mockToken = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');

		$RSAUsernamePasswordProvider = new \F3\FLOW3\Security\Authentication\Provider\RSAUsernamePassword();

		$this->assertTrue($RSAUsernamePasswordProvider->canAuthenticate($mockRSAUserNamePasswordToken));
		$this->assertFalse($RSAUsernamePasswordProvider->canAuthenticate($mockToken));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function providerInvalidatesKeypairsAfterTheAuthenticationProcess() {
		$mockToken = $this->getMock('F3\FLOW3\Security\Authentication\Token\RSAUsernamePassword', array(), array(), '', FALSE);
		$mockToken->expects($this->any())->method('getCredentials')->will($this->returnValue(array('encryptedUsername' => 'some fake value', 'encryptedPassword' => 'some other fake value')));
		$mockToken->expects($this->any())->method('getPasswordKeypairUUID')->will($this->returnValue('b2021eba-8084-4955-b2b6-a0a2c9bfad45'));
		$mockToken->expects($this->any())->method('getUsernameKeypairUUID')->will($this->returnValue('b2021eba-8084-4955-b2b6-a0a2c9bfad46'));
		$mockToken->expects($this->once())->method('invalidateCurrentKeypairs');

		$mockWalletService = $this->getMock('F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface', array(), array(), '', FALSE);
		$mockWalletService->expects($this->once())->method('decrypt')->will($this->returnValue('admin'));
		$mockWalletService->expects($this->once())->method('checkRSAEncryptedPassword')->will($this->returnValue(TRUE));

		$RSAUsernamePasswordProvider = new \F3\FLOW3\Security\Authentication\Provider\RSAUsernamePassword();
		$RSAUsernamePasswordProvider->injectRSAWalletService($mockWalletService);
		$RSAUsernamePasswordProvider->authenticate($mockToken);
	}
}
?>