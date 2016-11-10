<?php
namespace TYPO3\Flow\Cache\Backend;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Cache\Exception as CacheException;

/**
 * RequireOnceFromValueTrait
 */
trait RequireOnceFromValueTrait
{
    /**
     * @var array
     */
    protected $_requiredEntryIdentifiers = [];

    /**
     * Loads PHP code from the cache and require_onces it right away.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed Potential return value from the include operation
     * @api
     */
    public function requireOnce($entryIdentifier)
    {
        $value = trim($this->get($entryIdentifier));
        if ($value === '') {
            return false;
        }
        $hash = md5($value);
        if (isset($this->_requiredEntryIdentifiers[$hash])) {
            return false;
        }
        $this->_requiredEntryIdentifiers[$hash] = true;
        return include_once('data:text/plain;base64,' . base64_encode($value));
    }
}
