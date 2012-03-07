<?php
namespace TYPO3\FLOW3\Security\Cryptography;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use \TYPO3\FLOW3\Utility\Files;

/**
 * File based simple encrypted key service
 *
 * @FLOW3\Scope("singleton")
 */
class FileBasedSimpleKeyService {

	/**
	 * Pattern a key name must match.
	 */
	const PATTERN_KEYNAME = '/^[a-zA-Z0-9_%\-&]{1,250}$/';

	/**
	 * @var string
	 */
	protected $passwordHashingStrategy = 'default';

	/**
	 * @var integer
	 */
	protected $passwordGenerationLength = 8;

	/**
	 * @var \TYPO3\FLOW3\Security\Cryptography\HashService
	 * @FLOW3\Inject
	 */
	protected $hashService;

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		if (isset($settings['security']['cryptography']['fileBasedSimpleKeyService']['passwordHashingStrategy'])) {
			$this->passwordHashingStrategy = $settings['security']['cryptography']['fileBasedSimpleKeyService']['passwordHashingStrategy'];
		}
		if (isset($settings['security']['cryptography']['fileBasedSimpleKeyService']['passwordGenerationLength'])) {
			$this->passwordGenerationLength = $settings['security']['cryptography']['fileBasedSimpleKeyService']['passwordGenerationLength'];
		}
	}

	/**
	 * Generates a new key & saves it encrypted with a hashing strategy
	 *
	 * @param string $name
	 * @return string
	 * @throws \TYPO3\FLOW3\Security\Exception
	 */
	public function generateKey($name) {
		if (strlen($name) === 0) {
			throw new \TYPO3\FLOW3\Security\Exception('Required name argument was empty', 1334215474);
		}
		$password = \TYPO3\FLOW3\Utility\Algorithms::generateRandomString($this->passwordGenerationLength);
		$this->persistKey($name, $password);
		return $password;
	}

	/**
	 * Saves a key encrypted with a hashing strategy
	 *
	 * @param string $name
	 * @param string $password
	 * @return void
	 * @throws \TYPO3\FLOW3\Security\Exception
	 */
	public function storeKey($name, $password) {
		if (strlen($name) === 0) {
			throw new \TYPO3\FLOW3\Security\Exception('Required name argument was empty', 1334215443);
		}
		if (strlen($password) === 0) {
			throw new \TYPO3\FLOW3\Security\Exception('Required password argument was empty', 1334215349);
		}
		$this->persistKey($name, $password);
	}

	/**
	 * Checks if a key exists
	 *
	 * @param string $name
	 * @return boolean
	 * @throws \TYPO3\FLOW3\Security\Exception
	 */
	public function keyExists($name) {
		if (strlen($name) === 0) {
			throw new \TYPO3\FLOW3\Security\Exception('Required name argument was empty', 1334215344);
		}
		if (!file_exists($this->getKeyPathAndFilename($name))) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Returns a key by its name
	 *
	 * @param string $name
	 * @return boolean
	 * @throws \TYPO3\FLOW3\Security\Exception
	 */
	public function getKey($name) {
		if (strlen($name) === 0) {
			throw new \TYPO3\FLOW3\Security\Exception('Required name argument was empty', 1334215378);
		}
		$keyPathAndFileName = $this->getKeyPathAndFilename($name);
		if (!file_exists($keyPathAndFileName)) {
			throw new \TYPO3\FLOW3\Security\Exception(sprintf('The key "%s" does not exist.', $keyPathAndFileName), 1305812921);
		}
		$key = Files::getFileContents($keyPathAndFileName);
		if ($key === FALSE) {
			throw new \TYPO3\FLOW3\Security\Exception(sprintf('The key "%s" could not be read.', $keyPathAndFileName), 1334483163);
		}
		if (strlen($key) === 0) {
			throw new \TYPO3\FLOW3\Security\Exception(sprintf('The key "%s" is empty.', $keyPathAndFileName), 1334483165);
		}
		return $key;
	}

	/**
	 * Persists a key to the file system
	 *
	 * @param string $name
	 * @param string $password
	 * @return void
	 * @throws \TYPO3\FLOW3\Security\Exception
	 */
	protected function persistKey($name, $password) {
		$hashedPassword = $this->hashService->hashPassword($password, $this->passwordHashingStrategy);
		$keyPathAndFileName = $this->getKeyPathAndFilename($name);
		if (!is_dir($this->getPath())) {
			Files::createDirectoryRecursively($this->getPath());
		}
		$result = file_put_contents($keyPathAndFileName, $hashedPassword);
		if ($result === FALSE) {
			throw new \TYPO3\FLOW3\Security\Exception(sprintf('The key could not be stored ("%s").', $keyPathAndFileName), 1305812921);
		}
	}

	/**
	 * Returns the path and filename for the key with the given name.
	 *
	 * @param string $name
	 * @return string
	 */
	protected function getKeyPathAndFilename($name) {
		return Files::concatenatePaths(array($this->getPath(), $this->checkKeyName($name)));
	}

	/**
	 * Checks if the given key name is valid amd returns it
	 * (unchanged) if yes. Otherwise it throws an exception.
	 *
	 * @param string $name
	 * @return string
	 * @throws \TYPO3\FLOW3\Security\Exception
	 */
	protected function checkKeyName($name) {
		if (preg_match(self::PATTERN_KEYNAME, $name) !== 1) {
			throw new \TYPO3\FLOW3\Security\Exception('The key name "' . $name . '" is not valid.', 1334219077);
		}
		return $name;
	}

	/**
	 * Helper function to get the base path for key storage.
	 *
	 * @return string
	 */
	protected function getPath() {
		return Files::concatenatePaths(array(FLOW3_PATH_DATA, 'Persistent', 'FileBasedSimpleKeyService'));
	}

}
?>