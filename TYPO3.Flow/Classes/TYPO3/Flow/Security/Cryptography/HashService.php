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
use TYPO3\Flow\Cache\Frontend\StringFrontend;
use TYPO3\Flow\Utility\Algorithms as UtilityAlgorithms;

/**
 * A hash service which should be used to generate and validate hashes.
 *
 * @Flow\Scope("singleton")
 */
class HashService
{
    /**
     * A private, unique key used for encryption tasks
     * @var string
     */
    protected $encryptionKey = null;

    /**
     * @var array
     */
    protected $passwordHashingStrategies = array();

    /**
     * @var array
     */
    protected $strategySettings;

    /**
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var StringFrontend
     * @Flow\Inject
     */
    protected $cache;

    /**
     * Injects the settings of the package this controller belongs to.
     *
     * @param array $settings Settings container of the current package
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->strategySettings = $settings['security']['cryptography']['hashingStrategies'];
    }

    /**
     * Generate a hash (HMAC) for a given string
     *
     * @param string $string The string for which a hash should be generated
     * @return string The hash of the string
     * @throws \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException if something else than a string was given as parameter
     */
    public function generateHmac($string)
    {
        if (!is_string($string)) {
            throw new \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException('A hash can only be generated for a string, but "' . gettype($string) . '" was given.', 1255069587);
        }

        return hash_hmac('sha1', $string, $this->getEncryptionKey());
    }

    /**
     * Appends a hash (HMAC) to a given string and returns the result
     *
     * @param string $string The string for which a hash should be generated
     * @return string The original string with HMAC of the string appended
     * @see generateHmac()
     * @todo Mark as API once it is more stable
     */
    public function appendHmac($string)
    {
        $hmac = $this->generateHmac($string);
        return $string . $hmac;
    }

    /**
     * Tests if a string $string matches the HMAC given by $hash.
     *
     * @param string $string The string which should be validated
     * @param string $hmac The hash of the string
     * @return boolean TRUE if string and hash fit together, FALSE otherwise.
     */
    public function validateHmac($string, $hmac)
    {
        return ($this->generateHmac($string) === $hmac);
    }


    /**
     * Tests if the last 40 characters of a given string $string
     * matches the HMAC of the rest of the string and, if true,
     * returns the string without the HMAC. In case of a HMAC
     * validation error, an exception is thrown.
     *
     * @param string $string The string with the HMAC appended (in the format 'string<HMAC>')
     * @return string the original string without the HMAC, if validation was successful
     * @see validateHmac()
     * @throws \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException if the given string is not well-formatted
     * @throws \TYPO3\Flow\Security\Exception\InvalidHashException if the hash did not fit to the data.
     * @todo Mark as API once it is more stable
     */
    public function validateAndStripHmac($string)
    {
        if (!is_string($string)) {
            throw new \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException('A hash can only be validated for a string, but "' . gettype($string) . '" was given.', 1320829762);
        }
        if (strlen($string) < 40) {
            throw new \TYPO3\Flow\Security\Exception\InvalidArgumentForHashGenerationException('A hashed string must contain at least 40 characters, the given string was only ' . strlen($string) . ' characters long.', 1320830276);
        }
        $stringWithoutHmac = substr($string, 0, -40);
        if ($this->validateHmac($stringWithoutHmac, substr($string, -40)) !== true) {
            throw new \TYPO3\Flow\Security\Exception\InvalidHashException('The given string was not appended with a valid HMAC.', 1320830018);
        }
        return $stringWithoutHmac;
    }
    /**
     * Hash a password using the configured password hashing strategy
     *
     * @param string $password The cleartext password
     * @param string $strategyIdentifier An identifier for a configured strategy, uses default strategy if not specified
     * @return string A hashed password with salt (if used)
     * @api
     */
    public function hashPassword($password, $strategyIdentifier = 'default')
    {
        /** @var $passwordHashingStrategy PasswordHashingStrategyInterface */
        list($passwordHashingStrategy, $strategyIdentifier) = $this->getPasswordHashingStrategyAndIdentifier($strategyIdentifier, false);
        $hashedPasswordAndSalt = $passwordHashingStrategy->hashPassword($password, $this->getEncryptionKey());
        return $strategyIdentifier . '=>' . $hashedPasswordAndSalt;
    }

    /**
     * Validate a hashed password using the configured password hashing strategy
     *
     * @param string $password The cleartext password
     * @param string $hashedPasswordAndSalt The hashed password with salt (if used) and an optional strategy identifier
     * @return boolean TRUE if the given password matches the hashed password
     * @api
     */
    public function validatePassword($password, $hashedPasswordAndSalt)
    {
        $strategyIdentifier = 'default';
        if (strpos($hashedPasswordAndSalt, '=>') !== false) {
            list($strategyIdentifier, $hashedPasswordAndSalt) = explode('=>', $hashedPasswordAndSalt, 2);
        }

        /** @var $passwordHashingStrategy PasswordHashingStrategyInterface */
        list($passwordHashingStrategy, ) = $this->getPasswordHashingStrategyAndIdentifier($strategyIdentifier, true);
        return $passwordHashingStrategy->validatePassword($password, $hashedPasswordAndSalt, $this->getEncryptionKey());
    }

    /**
     * Get a password hashing strategy
     *
     * @param string $strategyIdentifier
     * @param boolean $validating TRUE if the password is validated, FALSE if the password is hashed
     * @return array Array of \TYPO3\Flow\Security\Cryptography\PasswordHashingStrategyInterface and string
     * @throws \TYPO3\Flow\Security\Exception\MissingConfigurationException
     */
    protected function getPasswordHashingStrategyAndIdentifier($strategyIdentifier = 'default', $validating)
    {
        if (isset($this->passwordHashingStrategies[$strategyIdentifier])) {
            return array($this->passwordHashingStrategies[$strategyIdentifier], $strategyIdentifier);
        }

        if ($strategyIdentifier === 'default') {
            if ($validating && isset($this->strategySettings['fallback'])) {
                $strategyIdentifier = $this->strategySettings['fallback'];
            } else {
                if (!isset($this->strategySettings['default'])) {
                    throw new \TYPO3\Flow\Security\Exception\MissingConfigurationException('No default hashing strategy configured', 1320758427);
                }
                $strategyIdentifier = $this->strategySettings['default'];
            }
        }

        if (!isset($this->strategySettings[$strategyIdentifier])) {
            throw new \TYPO3\Flow\Security\Exception\MissingConfigurationException('No hashing strategy with identifier "' . $strategyIdentifier . '" configured', 1320758776);
        }
        $strategyObjectName = $this->strategySettings[$strategyIdentifier];
        $this->passwordHashingStrategies[$strategyIdentifier] = $this->objectManager->get($strategyObjectName);
        return array($this->passwordHashingStrategies[$strategyIdentifier], $strategyIdentifier);
    }

    /**
     * Returns the encryption key from the persistent cache or Data/Persistent directory. If none exists, a new
     * encryption key will be generated and stored in the cache.
     *
     * @return string The configured encryption key stored in Data/Persistent/EncryptionKey
     */
    protected function getEncryptionKey()
    {
        if ($this->encryptionKey === null) {
            $this->encryptionKey = $this->cache->get('encryptionKey');
        }
        if ($this->encryptionKey === false && file_exists(FLOW_PATH_DATA . 'Persistent/EncryptionKey')) {
            $this->encryptionKey = file_get_contents(FLOW_PATH_DATA . 'Persistent/EncryptionKey');
        }
        if ($this->encryptionKey === false) {
            $this->encryptionKey = UtilityAlgorithms::generateRandomToken(48);
            $this->cache->set('encryptionKey', $this->encryptionKey);
        }
        return $this->encryptionKey;
    }
}
