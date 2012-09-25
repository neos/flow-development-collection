<?php
namespace TYPO3\Flow\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Command controller for tasks related to security
 *
 * @Flow\Scope("singleton")
 */
class SecurityCommandController extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @var \TYPO3\Flow\Security\Cryptography\RsaWalletServicePhp
	 * @Flow\Inject
	 */
	protected $rsaWalletService;

	/**
	 * Import a public key
	 *
	 * Read a PEM formatted public key from stdin and import it into the
	 * RSAWalletService.
	 *
	 * @return void
	 * @see typo3.flow:security:importprivatekey
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
	 * @see typo3.flow:security:importpublickey
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