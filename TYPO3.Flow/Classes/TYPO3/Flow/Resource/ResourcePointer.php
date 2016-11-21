<?php
namespace TYPO3\Flow\Resource;

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
use TYPO3\Flow\Resource\Resource as PersistentResource;

/**
 * Model describing a resource pointer
 *
 * This class is deprecated. Please simply use the Resource->getHash() method instead.
 *
 * @deprecated
 * @see PersistentResource
 */
class ResourcePointer
{
    /**
     * @var string
     */
    protected $Persistence_Object_Identifier;

    /**
     * @var string
     */
    protected $hash;

    /**
     * Constructs this resource pointer
     *
     * @param string $hash
     * @throws \InvalidArgumentException
     * @deprecated
     */
    public function __construct($hash)
    {
        $this->hash = $hash;
    }

    /**
     * Returns the hash of this resource
     *
     * @return string A 40 character hexadecimal sha1 hash over the content of this resource
     * @deprecated
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Returns a string representation of this resource object.
     *
     * @return string The hash of this resource
     * @deprecated
     */
    public function __toString()
    {
        return $this->hash;
    }
}
