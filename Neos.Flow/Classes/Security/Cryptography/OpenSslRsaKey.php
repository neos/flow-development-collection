<?php
namespace Neos\Flow\Security\Cryptography;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


/**
 * An RSA key
 *
 */
class OpenSslRsaKey
{
    /**
     * @var string
     */
    protected $modulus;

    /**
     * @var string
     */
    protected $keyString;

    /**
     * Constructor
     *
     * @param string $modulus The HEX modulus
     * @param string $keyString The private key string
     */
    public function __construct($modulus, $keyString)
    {
        $this->modulus = $modulus;
        $this->keyString = $keyString;
    }

    /**
     * Returns the modulus in HEX representation
     *
     * @return string The modulus
     */
    public function getModulus()
    {
        return $this->modulus;
    }

    /**
     * Returns the key string
     *
     * @return string The key string
     */
    public function getKeyString()
    {
        return $this->keyString;
    }
}
