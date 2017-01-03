<?php
namespace Neos\Cache\Backend;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Backend\AbstractBackend as IndependentAbstractBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Exception;
use Neos\Cache\Exception\InvalidDataException;
use Neos\Cache\Frontend\FrontendInterface;

/**
 * A caching backend which stores cache entries by using Memcache/Memcached.
 *
 * This backend uses the following types of cache keys:
 * - tag_xxx
 *   xxx is tag name, value is array of associated identifiers identifier. This
 *   is "forward" tag index. It is mainly used for obtaining content by tag
 *   (get identifier by tag -> get content by identifier)
 * - ident_xxx
 *   xxx is identifier, value is array of associated tags. This is "reverse" tag
 *   index. It provides quick access for all tags associated with this identifier
 *   and used when removing the identifier
 * - tagIndex
 *   Value is a List of all tags (array)
 *
 * Each key is prepended with a prefix. By default prefix consists from two parts
 * separated by underscore character and ends in yet another underscore character:
 * - "Flow"
 * - MD5 of script path and filename and SAPI name
 * This prefix makes sure that keys from the different installations do not
 * conflict.
 *
 * Note: When using the Memcache backend to store values of more than ~1 MB, the
 * data will be split into chunks to make them fit into the caches limits.
 *
 * @api
 */
class MemcachedBackend extends IndependentAbstractBackend implements TaggableBackendInterface, PhpCapableBackendInterface
{
    use RequireOnceFromValueTrait;

    /**
     * Max bucket size, (1024*1024)-42 bytes
     * @var int
     */
    const MAX_BUCKET_SIZE = 1048534;

    /**
     * Instance of the PHP Memcache/Memcached class
     *
     * @var \Memcache|\Memcached
     */
    protected $memcache;

    /**
     * Array of Memcache server configurations
     *
     * @var array
     */
    protected $servers = [];

    /**
     * Indicates whether the memcache uses compression or not (requires zlib),
     * either 0 or MEMCACHE_COMPRESSED
     *
     * @var integer
     */
    protected $flags;

    /**
     * A prefix to separate stored data from other data possible stored in the memcache
     *
     * @var string
     */
    protected $identifierPrefix;

    /**
     * {@inheritdoc}
     */
    public function __construct(EnvironmentConfiguration $environmentConfiguration, array $options = [])
    {
        if (!extension_loaded('memcache') && !extension_loaded('memcached')) {
            throw new Exception('The PHP extension "memcache" or "memcached" must be installed and loaded in order to use the Memcache backend.', 1213987706);
        }
        parent::__construct($environmentConfiguration, $options);
    }

    /**
     * Setter for servers to be used. Expects an array,  the values are expected
     * to be formatted like "<host>[:<port>]" or "unix://<path>"
     *
     * @param array $servers An array of servers to add.
     * @return void
     * @throws Exception
     * @api
     */
    protected function setServers(array $servers)
    {
        $this->servers = $servers;
        if (!count($this->servers)) {
            throw new Exception('No servers were given to Memcache', 1213115903);
        }

        $this->memcache = extension_loaded('memcached') ? new \MemCached() : new \Memcache();
        $defaultPort = ini_get('memcache.default_port') ?: 11211;

        foreach ($this->servers as $server) {
            $host = $server;
            $port = 0;

            if (strpos($server, 'tcp://') === 0) {
                $port = $defaultPort;
                $server = substr($server, 6);

                if (strpos($server, ':') !== false) {
                    list($host, $port) = explode(':', $server, 2);
                }
            }

            $this->memcache->addServer($host, $port);
        }
    }

    /**
     * Setter for compression flags bit
     *
     * @param boolean $useCompression
     * @return void
     * @api
     */
    protected function setCompression($useCompression)
    {
        if ($this->memcache instanceof \Memcached) {
            $this->memcache->setOption(\Memcached::OPT_COMPRESSION, $useCompression);
        } else {
            if ($useCompression === true) {
                $this->flags ^= MEMCACHE_COMPRESSED;
            } else {
                $this->flags &= ~MEMCACHE_COMPRESSED;
            }
        }
    }

    /**
     * Initializes the identifier prefix when setting the cache.
     *
     * @param FrontendInterface $cache
     * @return void
     */
    public function setCache(FrontendInterface $cache)
    {
        parent::setCache($cache);

        $pathHash = substr(md5($this->environmentConfiguration->getApplicationIdentifier() . $cache->getIdentifier()), 0, 12);
        $this->identifierPrefix = 'Flow_' . $pathHash . '_';
    }

    /**
     * Returns the internally used, prefixed entry identifier for the given public
     * entry identifier.
     *
     * While Flow applications will mostly refer to the simple entry identifier, it
     * may be necessary to know the actual identifier used by the cache backend
     * in order to share cache entries with other applications. This method allows
     * for retrieving it.
     *
     * @param string $entryIdentifier The short entry identifier, for example "NumberOfPostedArticles"
     * @return string The prefixed identifier, for example "Flow694a5c7a43a4_NumberOfPostedArticles"
     * @api
     */
    public function getPrefixedIdentifier($entryIdentifier)
    {
        return $this->identifierPrefix . $entryIdentifier;
    }

    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry
     * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @return void
     * @throws Exception if no cache frontend has been set.
     * @throws \InvalidArgumentException if the identifier is not valid or the final memcached key is longer than 250 characters
     * @throws InvalidDataException if $data is not a string
     * @api
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        if (strlen($this->identifierPrefix . $entryIdentifier) > 250) {
            throw new \InvalidArgumentException('Could not set value. Key more than 250 characters (' . $this->identifierPrefix . $entryIdentifier . ').', 1232969508);
        }
        if (!$this->cache instanceof FrontendInterface) {
            throw new Exception('No cache frontend has been set yet via setCache().', 1207149215);
        }
        if (!is_string($data)) {
            throw new InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1207149231);
        }

        $tags[] = '%MEMCACHEBE%' . $this->cacheIdentifier;
        $expiration = $lifetime !== null ? $lifetime : $this->defaultLifetime;
        // Memcache considers values over 2592000 sec (30 days) as UNIX timestamp
        // thus $expiration should be converted from lifetime to UNIX timestamp
        if ($expiration > 2592000) {
            $expiration += time();
        }

        try {
            if (strlen($data) > self::MAX_BUCKET_SIZE) {
                $data = str_split($data, self::MAX_BUCKET_SIZE - 1024);
                $success = true;
                $chunkNumber = 1;
                foreach ($data as $chunk) {
                    $success = $success && $this->setItem($this->identifierPrefix . $entryIdentifier . '_chunk_' . $chunkNumber, $chunk, $expiration);
                    $chunkNumber++;
                }
                $success = $success && $this->setItem($this->identifierPrefix . $entryIdentifier, 'Flow*chunked:' . $chunkNumber, $expiration);
            } else {
                $success = $this->setItem($this->identifierPrefix . $entryIdentifier, $data, $expiration);
            }
            if ($success === true) {
                $this->removeIdentifierFromAllTags($entryIdentifier);
                $this->addIdentifierToTags($entryIdentifier, $tags);
            } else {
                throw new Exception('Could not set value on memcache server.', 1275830266);
            }
        } catch (\Exception $exception) {
            throw new Exception('Could not set value. ' . $exception->getMessage(), 1207208100);
        }
    }

    /**
     * Stores an item on the server
     *
     * @param string $key
     * @param string $value
     * @param integer $expiration
     * @return boolean
     */
    protected function setItem($key, $value, $expiration)
    {
        if ($this->memcache instanceof \Memcached) {
            return $this->memcache->set($key, $value, $expiration);
        }
        return $this->memcache->set($key, $value, $this->flags, $expiration);
    }

    /**
     * Loads data from the cache.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
     * @api
     */
    public function get($entryIdentifier)
    {
        $value = $this->memcache->get($this->identifierPrefix . $entryIdentifier);
        if (substr($value, 0, 13) === 'Flow*chunked:') {
            list(, $chunkCount) = explode(':', $value);
            $value = '';
            for ($chunkNumber = 1; $chunkNumber < $chunkCount; $chunkNumber++) {
                $value .= $this->memcache->get($this->identifierPrefix . $entryIdentifier . '_chunk_' . $chunkNumber);
            }
        }
        return $value;
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return boolean TRUE if such an entry exists, FALSE if not
     * @api
     */
    public function has($entryIdentifier)
    {
        return $this->memcache->get($this->identifierPrefix . $entryIdentifier) !== false;
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry but if - for what reason ever -
     * old entries for the identifier still exist, they are removed as well.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
     * @api
     */
    public function remove($entryIdentifier)
    {
        $this->removeIdentifierFromAllTags($entryIdentifier);
        return $this->memcache->delete($this->identifierPrefix . $entryIdentifier);
    }

    /**
     * Finds and returns all cache entry identifiers which are tagged by the
     * specified tag.
     *
     * @param string $tag The tag to search for
     * @return array An array with identifiers of all matching entries. An empty array if no entries matched
     * @api
     */
    public function findIdentifiersByTag($tag)
    {
        $identifiers = $this->memcache->get($this->identifierPrefix . 'tag_' . $tag);
        if ($identifiers !== false) {
            return (array) $identifiers;
        } else {
            return [];
        }
    }

    /**
     * Finds all tags for the given identifier. This function uses reverse tag
     * index to search for tags.
     *
     * @param string $identifier Identifier to find tags by
     * @return array Array with tags
     */
    protected function findTagsByIdentifier($identifier)
    {
        $tags = $this->memcache->get($this->identifierPrefix . 'ident_' . $identifier);
        return ($tags === false ? [] : (array)$tags);
    }

    /**
     * Removes all cache entries of this cache.
     *
     * @return void
     * @throws Exception
     * @api
     */
    public function flush()
    {
        if (!$this->cache instanceof FrontendInterface) {
            throw new Exception('Yet no cache frontend has been set via setCache().', 1204111376);
        }

        $this->flushByTag('%MEMCACHEBE%' . $this->cacheIdentifier);
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     * @return integer The number of entries which have been affected by this flush
     * @api
     */
    public function flushByTag($tag)
    {
        $identifiers = $this->findIdentifiersByTag($tag);
        foreach ($identifiers as $identifier) {
            $this->remove($identifier);
        }
        return count($identifiers);
    }

    /**
     * Associates the identifier with the given tags
     *
     * @param string $entryIdentifier
     * @param array $tags
     * @return void
     */
    protected function addIdentifierToTags($entryIdentifier, array $tags)
    {
        foreach ($tags as $tag) {
            // Update tag-to-identifier index
            $identifiers = $this->findIdentifiersByTag($tag);
            if (array_search($entryIdentifier, $identifiers) === false) {
                $identifiers[] = $entryIdentifier;
                $this->memcache->set($this->identifierPrefix . 'tag_' . $tag, $identifiers);
            }

            // Update identifier-to-tag index
            $existingTags = $this->findTagsByIdentifier($entryIdentifier);
            if (array_search($tag, $existingTags) === false) {
                $this->memcache->set($this->identifierPrefix . 'ident_' . $entryIdentifier, array_merge($existingTags, $tags));
            }
        }
    }

    /**
     * Removes association of the identifier with the given tags
     *
     * @param string $entryIdentifier
     * @return void
     */
    protected function removeIdentifierFromAllTags($entryIdentifier)
    {
        // Get tags for this identifier
        $tags = $this->findTagsByIdentifier($entryIdentifier);
        // Deassociate tags with this identifier
        foreach ($tags as $tag) {
            $identifiers = $this->findIdentifiersByTag($tag);
            // Formally array_search() below should never return false due to
            // the behavior of findTagsByIdentifier(). But if reverse index is
            // corrupted, we still can get 'false' from array_search(). This is
            // not a problem because we are removing this identifier from
            // anywhere.
            if (($key = array_search($entryIdentifier, $identifiers)) !== false) {
                unset($identifiers[$key]);
                if (count($identifiers)) {
                    $this->memcache->set($this->identifierPrefix . 'tag_' . $tag, $identifiers);
                } else {
                    $this->memcache->delete($this->identifierPrefix . 'tag_' . $tag);
                }
            }
        }
        // Clear reverse tag index for this identifier
        $this->memcache->delete($this->identifierPrefix . 'ident_' . $entryIdentifier);
    }

    /**
     * Does nothing, as memcache/memcached does GC itself
     *
     * @return void
     * @api
     */
    public function collectGarbage()
    {
    }
}
