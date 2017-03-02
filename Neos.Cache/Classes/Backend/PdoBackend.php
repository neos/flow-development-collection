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
use Neos\Cache\Exception;
use Neos\Cache\Exception\InvalidDataException;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Utility\Files;
use Neos\Utility\PdoHelper;

/**
 * A PDO database cache backend
 *
 * @api
 */
class PdoBackend extends IndependentAbstractBackend implements TaggableBackendInterface, IterableBackendInterface, PhpCapableBackendInterface
{
    use RequireOnceFromValueTrait;

    /**
     * @var string
     */
    protected $dataSourceName;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var \PDO
     */
    protected $databaseHandle;

    /**
     * @var string
     */
    protected $pdoDriver;

    /**
     * @var string
     */
    protected $context;

    /**
     * @var \ArrayIterator
     */
    protected $cacheEntriesIterator = null;

    /**
     * Sets the DSN to use
     *
     * @param string $DSN The DSN to use for connecting to the DB
     * @return void
     * @api
     */
    public function setDataSourceName($DSN)
    {
        $this->dataSourceName = $DSN;
    }

    /**
     * Sets the username to use
     *
     * @param string $username The username to use for connecting to the DB
     * @return void
     * @api
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Sets the password to use
     *
     * @param string $password The password to use for connecting to the DB
     * @return void
     * @api
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Initialize the cache backend.
     *
     * @return void
     */
    public function initializeObject()
    {
    }

    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry
     * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
     * @return void
     * @throws Exception if no cache frontend has been set.
     * @throws \InvalidArgumentException if the identifier is not valid
     * @throws InvalidDataException if $data is not a string
     * @api
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        $this->connect();

        if (!$this->cache instanceof FrontendInterface) {
            throw new Exception('No cache frontend has been set yet via setCache().', 1259515600);
        }
        if (!is_string($data)) {
            throw new InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1259515601);
        }

        $this->remove($entryIdentifier);

        $lifetime = ($lifetime === null) ? $this->defaultLifetime : $lifetime;

        // Convert binary data into hexadecimal representation,
        // because it is not allowed to store null bytes in PostgreSQL.
        if ($this->pdoDriver === 'pgsql') {
            $data = bin2hex($data);
        }

        $statementHandle = $this->databaseHandle->prepare('INSERT INTO "cache" ("identifier", "context", "cache", "created", "lifetime", "content") VALUES (?, ?, ?, ?, ?, ?)');
        $result = $statementHandle->execute([$entryIdentifier, $this->context(), $this->cacheIdentifier, time(), $lifetime, $data]);
        if ($result === false) {
            throw new Exception('The cache entry "' . $entryIdentifier . '" could not be written.', 1259530791);
        }

        $statementHandle = $this->databaseHandle->prepare('INSERT INTO "tags" ("identifier", "context", "cache", "tag") VALUES (?, ?, ?, ?)');
        foreach ($tags as $tag) {
            $result = $statementHandle->execute([$entryIdentifier, $this->context(), $this->cacheIdentifier, $tag]);
            if ($result === false) {
                throw new Exception('The tag "' . $tag . ' for cache entry "' . $entryIdentifier . '" could not be written.', 1259530751);
            }
        }
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
        $this->connect();

        $statementHandle = $this->databaseHandle->prepare('SELECT "content" FROM "cache" WHERE "identifier"=? AND "context"=? AND "cache"=?' . $this->getNotExpiredStatement());
        $statementHandle->execute([$entryIdentifier, $this->context(), $this->cacheIdentifier]);
        $fetchedColumn = $statementHandle->fetchColumn();

        // Convert hexadecimal data into binary string,
        // because it is not allowed to store null bytes in PostgreSQL.
        if ($fetchedColumn !== false && $this->pdoDriver === 'pgsql') {
            $fetchedColumn = hex2bin($fetchedColumn);
        }

        return $fetchedColumn;
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
        $this->connect();

        $statementHandle = $this->databaseHandle->prepare('SELECT COUNT("identifier") FROM "cache" WHERE "identifier"=? AND "context"=? AND "cache"=?' . $this->getNotExpiredStatement());
        $statementHandle->execute([$entryIdentifier, $this->context(), $this->cacheIdentifier]);
        return ($statementHandle->fetchColumn() > 0);
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
        $this->connect();

        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "tags" WHERE "identifier"=? AND "context"=? AND "cache"=?');
        $statementHandle->execute([$entryIdentifier, $this->context(), $this->cacheIdentifier]);

        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "cache" WHERE "identifier"=? AND "context"=? AND "cache"=?');
        $statementHandle->execute([$entryIdentifier, $this->context(), $this->cacheIdentifier]);

        return ($statementHandle->rowCount() > 0);
    }

    /**
     * Removes all cache entries of this cache.
     *
     * @return void
     * @api
     */
    public function flush()
    {
        $this->connect();

        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "tags" WHERE "context"=? AND "cache"=?');
        $statementHandle->execute([$this->context(), $this->cacheIdentifier]);

        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "cache" WHERE "context"=? AND "cache"=?');
        $statementHandle->execute([$this->context(), $this->cacheIdentifier]);
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     * @return void
     * @api
     */
    public function flushByTag($tag)
    {
        $this->connect();

        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "cache" WHERE "context"=? AND "cache"=? AND "identifier" IN (SELECT "identifier" FROM "tags" WHERE "context"=? AND "cache"=? AND "tag"=?)');
        $statementHandle->execute([$this->context(), $this->cacheIdentifier, $this->context(), $this->cacheIdentifier, $tag]);

        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "tags" WHERE "context"=? AND "cache"=? AND "tag"=?');
        $statementHandle->execute([$this->context(), $this->cacheIdentifier, $tag]);
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
        $this->connect();

        $statementHandle = $this->databaseHandle->prepare('SELECT "identifier" FROM "tags" WHERE "context"=?  AND "cache"=? AND "tag"=?');
        $statementHandle->execute([$this->context(), $this->cacheIdentifier, $tag]);
        return $statementHandle->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Does garbage collection
     *
     * @return void
     * @api
     */
    public function collectGarbage()
    {
        $this->connect();

        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "tags" WHERE "context"=? AND "cache"=? AND "identifier" IN (SELECT "identifier" FROM "cache" WHERE "context"=? AND "cache"=? AND "lifetime" > 0 AND "created" + "lifetime" < ' . time() . ')');
        $statementHandle->execute([$this->context(), $this->cacheIdentifier, $this->context(), $this->cacheIdentifier]);

        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "cache" WHERE "context"=? AND "cache"=? AND "lifetime" > 0 AND "created" + "lifetime" < ' . time());
        $statementHandle->execute([$this->context(), $this->cacheIdentifier]);
    }

    /**
     * Returns an SQL statement that evaluates to true if the entry is not expired.
     *
     * @return string
     */
    protected function getNotExpiredStatement()
    {
        return ' AND ("lifetime" = 0 OR "created" + "lifetime" >= ' . time() . ')';
    }

    /**
     * Connect to the database
     *
     * @return void
     * @throws Exception if the connection cannot be established
     */
    protected function connect()
    {
        if ($this->databaseHandle !== null) {
            return;
        }
        try {
            $splitdsn = explode(':', $this->dataSourceName, 2);
            $this->pdoDriver = $splitdsn[0];

            if ($this->pdoDriver === 'sqlite' && !file_exists($splitdsn[1])) {
                if (!file_exists(dirname($splitdsn[1]))) {
                    Files::createDirectoryRecursively(dirname($splitdsn[1]));
                }
                $this->databaseHandle = new \PDO($this->dataSourceName, $this->username, $this->password);
                $this->createCacheTables();
            } else {
                $this->databaseHandle = new \PDO($this->dataSourceName, $this->username, $this->password);
            }
            $this->databaseHandle->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            if ($this->pdoDriver === 'mysql') {
                $this->databaseHandle->exec('SET SESSION sql_mode=\'ANSI\';');
            }
        } catch (\PDOException $exception) {
            throw new Exception('Could not connect to cache table with DSN "' . $this->dataSourceName . '". PDO error: ' . $exception->getMessage(), 1334736164);
        }
    }

    /**
     * Creates the tables needed for the cache backend.
     *
     * @return void
     * @throws Exception if something goes wrong
     */
    protected function createCacheTables()
    {
        $this->connect();
        try {
            PdoHelper::importSql($this->databaseHandle, $this->pdoDriver, __DIR__ . '/../../Resources/Private/DDL.sql');
        } catch (\PDOException $exception) {
            throw new Exception('Could not create cache tables with DSN "' . $this->dataSourceName . '". PDO error: ' . $exception->getMessage(), 1259576985);
        }
    }

    /**
     * Returns the data of the current cache entry pointed to by the cache entry
     * iterator.
     *
     * @return mixed
     * @api
     */
    public function current()
    {
        if ($this->cacheEntriesIterator === null) {
            $this->rewind();
        }
        return $this->cacheEntriesIterator->current();
    }

    /**
     * Move forward to the next cache entry.
     *
     * @return void
     * @api
     */
    public function next()
    {
        if ($this->cacheEntriesIterator === null) {
            $this->rewind();
        }
        $this->cacheEntriesIterator->next();
    }

    /**
     * Returns the identifier of the current cache entry pointed to by the cache
     * entry iterator.
     *
     * @return string
     * @api
     */
    public function key()
    {
        if ($this->cacheEntriesIterator === null) {
            $this->rewind();
        }
        return $this->cacheEntriesIterator->key();
    }

    /**
     * Checks if the current position of the cache entry iterator is valid.
     *
     * @return boolean TRUE if the current position is valid, otherwise FALSE
     * @api
     */
    public function valid()
    {
        if ($this->cacheEntriesIterator === null) {
            $this->rewind();
        }
        return $this->cacheEntriesIterator->valid();
    }

    /**
     * Rewinds the cache entry iterator to the first element
     * and fetches cacheEntries.
     *
     * @return void
     * @api
     */
    public function rewind()
    {
        if ($this->cacheEntriesIterator === null) {
            $cacheEntries = [];

            $statementHandle = $this->databaseHandle->prepare('SELECT "identifier", "content" FROM "cache" WHERE "context"=? AND "cache"=?' . $this->getNotExpiredStatement());
            $statementHandle->execute(array($this->context(), $this->cacheIdentifier));
            $fetchedColumns = $statementHandle->fetchAll();

            foreach ($fetchedColumns as $fetchedColumn) {
                // Convert hexadecimal data into binary string,
                // because it is not allowed to store null bytes in PostgreSQL.
                if ($this->pdoDriver === 'pgsql') {
                    $fetchedColumn['content'] = hex2bin($fetchedColumn['content']);
                }

                $cacheEntries[$fetchedColumn['identifier']] = $fetchedColumn['content'];
            }

            $this->cacheEntriesIterator = new \ArrayIterator($cacheEntries);
        } else {
            $this->cacheEntriesIterator->rewind();
        }
    }

    /**
     * @return string
     */
    protected function context()
    {
        if ($this->context === null) {
            $this->context = md5($this->environmentConfiguration->getApplicationIdentifier());
        }
        return $this->context;
    }
}
