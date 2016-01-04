<?php
namespace TYPO3\Flow\Security\Cryptography;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Algorithms as UtilityAlgorithms;
use TYPO3\Flow\Utility\Files;

/**
 * File based simple encrypted key service
 *
 * @Flow\Scope("singleton")
 */
class FileBasedSimpleKeyService
{
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
     * @var \TYPO3\Flow\Security\Cryptography\HashService
     * @Flow\Inject
     */
    protected $hashService;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
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
     * @throws \TYPO3\Flow\Security\Exception
     */
    public function generateKey($name)
    {
        if (strlen($name) === 0) {
            throw new \TYPO3\Flow\Security\Exception('Required name argument was empty', 1334215474);
        }
        $password = UtilityAlgorithms::generateRandomString($this->passwordGenerationLength);
        $this->persistKey($name, $password);
        return $password;
    }

    /**
     * Saves a key encrypted with a hashing strategy
     *
     * @param string $name
     * @param string $password
     * @return void
     * @throws \TYPO3\Flow\Security\Exception
     */
    public function storeKey($name, $password)
    {
        if (strlen($name) === 0) {
            throw new \TYPO3\Flow\Security\Exception('Required name argument was empty', 1334215443);
        }
        if (strlen($password) === 0) {
            throw new \TYPO3\Flow\Security\Exception('Required password argument was empty', 1334215349);
        }
        $this->persistKey($name, $password);
    }

    /**
     * Checks if a key exists
     *
     * @param string $name
     * @return boolean
     * @throws \TYPO3\Flow\Security\Exception
     */
    public function keyExists($name)
    {
        if (strlen($name) === 0) {
            throw new \TYPO3\Flow\Security\Exception('Required name argument was empty', 1334215344);
        }
        if (!file_exists($this->getKeyPathAndFilename($name))) {
            return false;
        }
        return true;
    }

    /**
     * Returns a key by its name
     *
     * @param string $name
     * @return boolean
     * @throws \TYPO3\Flow\Security\Exception
     */
    public function getKey($name)
    {
        if (strlen($name) === 0) {
            throw new \TYPO3\Flow\Security\Exception('Required name argument was empty', 1334215378);
        }
        $keyPathAndFilename = $this->getKeyPathAndFilename($name);
        if (!file_exists($keyPathAndFilename)) {
            throw new \TYPO3\Flow\Security\Exception(sprintf('The key "%s" does not exist.', $keyPathAndFilename), 1305812921);
        }
        $key = Files::getFileContents($keyPathAndFilename);
        if ($key === false) {
            throw new \TYPO3\Flow\Security\Exception(sprintf('The key "%s" could not be read.', $keyPathAndFilename), 1334483163);
        }
        if (strlen($key) === 0) {
            throw new \TYPO3\Flow\Security\Exception(sprintf('The key "%s" is empty.', $keyPathAndFilename), 1334483165);
        }
        return $key;
    }

    /**
     * Persists a key to the file system
     *
     * @param string $name
     * @param string $password
     * @return void
     * @throws \TYPO3\Flow\Security\Exception
     */
    protected function persistKey($name, $password)
    {
        $hashedPassword = $this->hashService->hashPassword($password, $this->passwordHashingStrategy);
        $keyPathAndFilename = $this->getKeyPathAndFilename($name);
        if (!is_dir($this->getPath())) {
            Files::createDirectoryRecursively($this->getPath());
        }
        $result = file_put_contents($keyPathAndFilename, $hashedPassword);
        if ($result === false) {
            throw new \TYPO3\Flow\Security\Exception(sprintf('The key could not be stored ("%s").', $keyPathAndFilename), 1305812921);
        }
    }

    /**
     * Returns the path and filename for the key with the given name.
     *
     * @param string $name
     * @return string
     */
    protected function getKeyPathAndFilename($name)
    {
        return Files::concatenatePaths(array($this->getPath(), $this->checkKeyName($name)));
    }

    /**
     * Checks if the given key name is valid amd returns it
     * (unchanged) if yes. Otherwise it throws an exception.
     *
     * @param string $name
     * @return string
     * @throws \TYPO3\Flow\Security\Exception
     */
    protected function checkKeyName($name)
    {
        if (preg_match(self::PATTERN_KEYNAME, $name) !== 1) {
            throw new \TYPO3\Flow\Security\Exception('The key name "' . $name . '" is not valid.', 1334219077);
        }
        return $name;
    }

    /**
     * Helper function to get the base path for key storage.
     *
     * @return string
     */
    protected function getPath()
    {
        return Files::concatenatePaths(array(FLOW_PATH_DATA, 'Persistent', 'FileBasedSimpleKeyService'));
    }
}
