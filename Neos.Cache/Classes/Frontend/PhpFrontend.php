<?php
declare(strict_types=1);

namespace Neos\Cache\Frontend;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\PhpCapableBackendInterface;
use Neos\Cache\Exception\InvalidDataException;

/**
 * A cache frontend tailored to PHP code.
 *
 * @api
 */
class PhpFrontend extends StringFrontend
{
    /**
     * @var PhpCapableBackendInterface
     */
    protected $backend;

    /**
     * Constructs the cache
     *
     * @param string $identifier A identifier which describes this cache
     * @param PhpCapableBackendInterface $backend Backend to be used for this cache
     */
    public function __construct(string $identifier, PhpCapableBackendInterface $backend)
    {
        parent::__construct($identifier, $backend);
    }

    /**
     * Finds and returns the original code from the cache.
     *
     * @param string $entryIdentifier Identifier of the cache entry to fetch
     * @return string|bool The value
     * @throws \InvalidArgumentException
     * @api
     */
    public function get(string $entryIdentifier)
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233057752);
        }
        $code = $this->backend->get($entryIdentifier);

        if ($code === false) {
            return false;
        }

        preg_match('/^(?:.*\n){1}((?:.*\n)*)(?:.+\n?|\n)$/', $code, $matches);

        return $matches[1];
    }

    /**
     * Returns the code wrapped in php tags as written to the cache, ready to be included.
     *
     * @param string $entryIdentifier
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getWrapped(string $entryIdentifier)
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233057752);
        }

        return $this->backend->get($entryIdentifier);
    }

    /**
     * Saves the PHP source code in the cache.
     *
     * @param string $entryIdentifier An identifier used for this cache entry, for example the class name
     * @param string $sourceCode PHP source code
     * @param array $tags Tags to associate with this cache entry
     * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @return void
     * @throws InvalidDataException
     * @throws \InvalidArgumentException
     * @throws \Neos\Cache\Exception
     * @api
     */
    public function set(string $entryIdentifier, $sourceCode, array $tags = [], int $lifetime = null)
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1264023823);
        }
        if (!is_string($sourceCode)) {
            throw new InvalidDataException('The given source code is not a valid string.', 1264023824);
        }
        foreach ($tags as $tag) {
            if (!$this->isValidTag($tag)) {
                throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1264023825);
            }
        }
        $sourceCode = '<?php ' . $sourceCode . chr(10) . '#';
        $this->backend->set($entryIdentifier, $sourceCode, $tags, $lifetime);
    }

    /**
     * Loads PHP code from the cache and require_onces it right away.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed Potential return value from the include operation
     * @api
     */
    public function requireOnce(string $entryIdentifier)
    {
        return $this->backend->requireOnce($entryIdentifier);
    }
}
