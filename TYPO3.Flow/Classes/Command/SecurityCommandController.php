<?php
namespace TYPO3\FLOW3\Command;

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
 * Command controller for tasks related to security
 *
 * @scope singleton
 */
class SecurityCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @var \TYPO3\FLOW3\Security\Cryptography\RsaWalletServicePhp
	 * @inject
	 */
	protected $rsaWalletService;

	/**
	 * Import a public key
	 *
	 * Read a PEM formatted public key from stdin and import it into the
	 * RSAWalletService.
	 *
	 * @return void
	 * @see typo3.flow3:security:importprivatekey
	 */
	public function importPublicKeyCommand() {
		$keyData = '';
			// no file_get_contents here because it does not work on php://stdin
		$fp = fopen('php://stdin', 'rb');
		while (!feof($fp)) {
			$keyData .= fgets($fp, 4096);
		}
		fclose($fp);

		$uuid = $this->rsaWalletService->registerPublicKeyFromString($keyData);

		$this->outputLine('The public key has been successfully imported. Use the following uuid to refer to it in the RSAWalletService: ' . PHP_EOL . PHP_EOL . $uuid . PHP_EOL);
	}

	/**
	 * Import a private key
	 *
	 * Read a PEM formatted private key from stdin and import it into the
	 * RSAWalletService. The public key will be automatically extracted and stored
	 * together with the private key as a key pair.
	 *
	 * @param boolean $usedForPasswords If the private key should be used for passwords
	 * @return void
	 * @see typo3.flow3:security:importpublickey
	 */
	public function importPrivateKeyCommand($usedForPasswords = FALSE) {
		$keyData = '';
			// no file_get_contents here because it does not work on php://stdin
		$fp = fopen('php://stdin', 'rb');
		while (!feof($fp)) {
			$keyData .= fgets($fp, 4096);
		}
		fclose($fp);

		$uuid = $this->rsaWalletService->registerKeyPairFromPrivateKeyString($keyData, $usedForPasswords);

		$this->outputLine('The keypair has been successfully imported. Use the following uuid to refer to it in the RSAWalletService: ' . PHP_EOL . PHP_EOL . $uuid . PHP_EOL);
	}
}

?>