<?php
declare(strict_types=1);

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
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Notice;
use Neos\Error\Messages\Result;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;
use Neos\Utility\PdoHelper;

/**
 * A PDO database cache backend
 *
 * @api
 */
class PdoBackend extends IndependentAbstractBackend implements TaggableBackendInterface, IterableBackendInterface, PhpCapableBackendInterface, WithSetupInterface, WithStatusInterface
{
    use RequireOnceFromValueTrait;

    /**
     * @var string
     */
    protected $dataSourceName = '';

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
     * @var string
     */
    protected $cacheTableName = 'cache';

    /**
     * @var string
     */
    protected $tagsTableName = 'tags';

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
    protected function setDataSourceName(string $DSN): void
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
    protected function setUsername(string $username): void
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
    protected function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * Sets the name of the "cache" table
     *
     * @param string $cacheTableName
     * @return void
     * @api
     */
    protected function setCacheTableName(string $cacheTableName): void
    {
        $this->cacheTableName = $cacheTableName;
    }

    /**
     * Sets the name of the "tags" table
     *
     * @param string $tagsTableName
     * @return void
     * @api
     */
    protected function setTagsTableName(string $tagsTableName): void
    {
        $this->tagsTableName = $tagsTableName;
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
     * @throws \InvalidArgumentException if the identifier is not valid
     * @throws FilesException
     * @api
     */
    public function set(string $entryIdentifier, string $data, array $tags = [], int $lifetime = null): void
    {
        $this->connect();

        if (!$this->cache instanceof FrontendInterface) {
            throw new Exception('No cache frontend has been set yet via setCache().', 1259515600);
        }

        $lifetime = ($lifetime === null) ? $this->defaultLifetime : $lifetime;

        // Convert binary data into hexadecimal representation,
        // because it is not allowed to store null bytes in PostgreSQL.
        if ($this->pdoDriver === 'pgsql') {
            $data = bin2hex($data);
        }

        $this->databaseHandle->beginTransaction();
        try {
            $this->removeWithoutTransaction($entryIdentifier);

            $statementHandle = $this->databaseHandle->prepare('INSERT INTO "' . $this->cacheTableName . '" ("identifier", "context", "cache", "created", "lifetime", "content") VALUES (?, ?, ?, ?, ?, ?)');
            $result = $statementHandle->execute([$entryIdentifier, $this->context(), $this->cacheIdentifier, time(), $lifetime, $data]);
            if ($result === false) {
                throw new Exception('The cache entry "' . $entryIdentifier . '" could not be written.', 1259530791);
            }

            $statementHandle = $this->databaseHandle->prepare('INSERT INTO "' . $this->tagsTableName . '" ("identifier", "context", "cache", "tag") VALUES (?, ?, ?, ?)');
            foreach ($tags as $tag) {
                $result = $statementHandle->execute([$entryIdentifier, $this->context(), $this->cacheIdentifier, $tag]);
                if ($result === false) {
                    throw new Exception('The tag "' . $tag . ' for cache entry "' . $entryIdentifier . '" could not be written.', 1259530751);
                }
            }

            $this->databaseHandle->commit();
        } catch (\Exception $exception) {
            $this->databaseHandle->rollBack();

            throw $exception;
        }
    }

    /**
     * Loads data from the cache.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed The cache entry's content as a string or false if the cache entry could not be loaded
     * @throws Exception
     * @api
     */
    public function get(string $entryIdentifier)
    {
        $this->connect();

        $statementHandle = $this->databaseHandle->prepare('SELECT "content" FROM "' . $this->cacheTableName . '" WHERE "identifier"=? AND "context"=? AND "cache"=?' . $this->getNotExpiredStatement());
        $statementHandle->execute([$entryIdentifier, $this->context(), $this->cacheIdentifier]);
        $fetchedColumn = $statementHandle->fetchColumn();

        // Convert hexadecimal data into binary string,
        // because it is not allowed to store null bytes in PostgreSQL.
        if ($this->pdoDriver === 'pgsql' && is_string($fetchedColumn)) {
            $fetchedColumn = hex2bin($fetchedColumn);
        }

        return $fetchedColumn;
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return boolean true if such an entry exists, false if not
     * @throws Exception
     * @api
     */
    public function has(string $entryIdentifier): bool
    {
        $this->connect();

        $statementHandle = $this->databaseHandle->prepare('SELECT COUNT("identifier") FROM "' . $this->cacheTableName . '" WHERE "identifier"=? AND "context"=? AND "cache"=?' . $this->getNotExpiredStatement());
        $statementHandle->execute([$entryIdentifier, $this->context(), $this->cacheIdentifier]);
        return ($statementHandle->fetchColumn() > 0);
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry but if - for what reason ever -
     * old entries for the identifier still exist, they are removed as well.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return boolean true if (at least) one entry could be removed or false if no entry was found
     * @throws Exception
     * @api
     */
    public function remove(string $entryIdentifier): bool
    {
        $this->connect();

        $this->databaseHandle->beginTransaction();
        try {
            $rowsWereDeleted = $this->removeWithoutTransaction($entryIdentifier);
            $this->databaseHandle->commit();

            return $rowsWereDeleted;
        } catch (\Exception $exception) {
            $this->databaseHandle->rollBack();

            throw $exception;
        }
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry but if - for what reason ever -
     * old entries for the identifier still exist, they are removed as well.
     *
     * Note: this does not wrap the removal statements into a transaction, as
     * such the method must only be used when a transaction is active!
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return boolean true if (at least) an entry could be removed or false if no entry was found
     */
    private function removeWithoutTransaction(string $entryIdentifier)
    {
        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "' . $this->tagsTableName . '" WHERE "identifier"=? AND "context"=? AND "cache"=?');
        $statementHandle->execute([$entryIdentifier, $this->context(), $this->cacheIdentifier]);

        $statementHandle = $this->databaseHandle->prepare('DELETE FROM "' . $this->cacheTableName . '" WHERE "identifier"=? AND "context"=? AND "cache"=?');
        $statementHandle->execute([$entryIdentifier, $this->context(), $this->cacheIdentifier]);

        return ($statementHandle->rowCount() > 0);
    }

    /**
     * Removes all cache entries of this cache.
     *
     * @return void
     * @throws Exception
     * @api
     */
    public function flush(): void
    {
        $this->connect();

        $this->databaseHandle->beginTransaction();
        try {
            $statementHandle = $this->databaseHandle->prepare('DELETE FROM "' . $this->tagsTableName . '" WHERE "context"=? AND "cache"=?');
            $statementHandle->execute([$this->context(), $this->cacheIdentifier]);

            $statementHandle = $this->databaseHandle->prepare('DELETE FROM "' . $this->cacheTableName . '" WHERE "context"=? AND "cache"=?');
            $statementHandle->execute([$this->context(), $this->cacheIdentifier]);

            $this->databaseHandle->commit();
        } catch (\Exception $exception) {
            $this->databaseHandle->rollBack();

            throw $exception;
        }
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     * @return integer
     * @throws Exception
     * @api
     */
    public function flushByTag(string $tag): int
    {
        $this->connect();

        $this->databaseHandle->beginTransaction();
        try {
            $statementHandle = $this->databaseHandle->prepare('DELETE FROM "' . $this->cacheTableName . '" WHERE "context"=? AND "cache"=? AND "identifier" IN (SELECT "identifier" FROM "tags" WHERE "context"=? AND "cache"=? AND "tag"=?)');
            $statementHandle->execute([$this->context(), $this->cacheIdentifier, $this->context(), $this->cacheIdentifier, $tag]);

            $flushed = $statementHandle->rowCount();

            $statementHandle = $this->databaseHandle->prepare('DELETE FROM "' . $this->tagsTableName . '" WHERE "context"=? AND "cache"=? AND "tag"=?');
            $statementHandle->execute([$this->context(), $this->cacheIdentifier, $tag]);

            $this->databaseHandle->commit();
        } catch (\Exception $exception) {
            $this->databaseHandle->rollBack();

            throw $exception;
        }

        return $flushed;
    }

    /**
     * Finds and returns all cache entry identifiers which are tagged by the
     * specified tag.
     *
     * @param string $tag The tag to search for
     * @return string[] An array with identifiers of all matching entries. An empty array if no entries matched
     * @throws Exception
     * @api
     */
    public function findIdentifiersByTag(string $tag): array
    {
        $this->connect();

        $statementHandle = $this->databaseHandle->prepare('SELECT "identifier" FROM "' . $this->tagsTableName . '" WHERE "context"=?  AND "cache"=? AND "tag"=?');
        $statementHandle->execute([$this->context(), $this->cacheIdentifier, $tag]);
        return $statementHandle->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Does garbage collection
     *
     * @return void
     * @throws Exception
     * @api
     */
    public function collectGarbage(): void
    {
        $this->connect();

        $this->databaseHandle->beginTransaction();
        try {
            $statementHandle = $this->databaseHandle->prepare('DELETE FROM "' . $this->tagsTableName . '" WHERE "context"=? AND "cache"=? AND "identifier" IN (SELECT "identifier" FROM "cache" WHERE "context"=? AND "cache"=? AND "lifetime" > 0 AND "created" + "lifetime" < ' . time() . ')');
            $statementHandle->execute([$this->context(), $this->cacheIdentifier, $this->context(), $this->cacheIdentifier]);

            $statementHandle = $this->databaseHandle->prepare('DELETE FROM "' . $this->cacheTableName . '" WHERE "context"=? AND "cache"=? AND "lifetime" > 0 AND "created" + "lifetime" < ' . time());
            $statementHandle->execute([$this->context(), $this->cacheIdentifier]);

            $this->databaseHandle->commit();
        } catch (\Exception $exception) {
            $this->databaseHandle->rollBack();

            throw $exception;
        }
    }

    /**
     * Returns an SQL statement that evaluates to true if the entry is not expired.
     *
     * @return string
     */
    protected function getNotExpiredStatement(): string
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
                    try {
                        Files::createDirectoryRecursively(dirname($splitdsn[1]));
                    } catch (FilesException $exception) {
                        throw new Exception(sprintf('Could not create directory for sqlite file "%s"', $splitdsn[1]), 1565359792, $exception);
                    }
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
            throw new Exception(sprintf('Could not connect to cache table with DSN "%s". PDO error: %s', $this->dataSourceName, $exception->getMessage()), 1334736164, $exception);
        }
    }

    /**
     * Creates the tables needed for the cache backend.
     *
     * @return void
     * @throws Exception if something goes wrong
     */
    protected function createCacheTables(): void
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
    public function key(): string
    {
        if ($this->cacheEntriesIterator === null) {
            $this->rewind();
        }
        return $this->cacheEntriesIterator->key();
    }

    /**
     * Checks if the current position of the cache entry iterator is valid.
     *
     * @return boolean true if the current position is valid, otherwise false
     * @api
     */
    public function valid(): bool
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
        try {
            $this->connect();
        } catch (Exception $e) {
            return;
        }

        if ($this->cacheEntriesIterator !== null) {
            $this->cacheEntriesIterator->rewind();
            return;
        }

        $cacheEntries = [];

        $statementHandle = $this->databaseHandle->prepare('SELECT "identifier", "content" FROM "' . $this->cacheTableName . '" WHERE "context"=? AND "cache"=?' . $this->getNotExpiredStatement());
        $statementHandle->execute([$this->context(), $this->cacheIdentifier]);
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
    }

    /**
     * @return string
     */
    protected function context(): string
    {
        if ($this->context === null) {
            $this->context = md5($this->environmentConfiguration->getApplicationIdentifier());
        }
        return $this->context;
    }

    /**
     * Connects to the configured PDO database and adds/updates table schema if required
     *
     * @return Result
     * @api
     */
    public function setup(): Result
    {
        $result = new Result();
        try {
            $this->connect();
        } catch (Exception $exception) {
            $result->addError(new Error($exception->getMessage(), (int)$exception->getCode(), [], 'Failed'));
        }
        if ($this->pdoDriver === 'sqlite') {
            $result->addNotice(new Notice('SQLite database tables are created automatically and don\'t need to be set up'));
            return $result;
        }
        $result->addNotice(new Notice('Creating database tables "%s" & "%s"...', null, [$this->cacheTableName, $this->tagsTableName]));
        try {
            $this->createCacheTables();
        } catch (Exception $exception) {
            $result->addError(new Error($exception->getMessage(), (int)$exception->getCode(), [], 'Failed'));
        }
        return $result;
    }

    /**
     * Validates that configured database is accessible and schema up to date
     *
     * @return Result
     * @api
     */
    public function getStatus(): Result
    {
        $result = new Result();
        try {
            $this->connect();
        } catch (Exception $exception) {
            $result->addError(new Error($exception->getMessage(), (int)$exception->getCode(), [], 'Connection failed'));
            return $result;
        }
        $result->addNotice(new Notice($this->pdoDriver, null, [], 'Driver'));
        if (!$this->tableExists($this->cacheTableName)) {
            $result->addError(new Error('%s (missing)', null, [$this->cacheTableName], 'Table'));
        }
        if (!$this->tableExists($this->tagsTableName)) {
            $result->addError(new Error('%s (missing)', null, [$this->tagsTableName], 'Table'));
        }

        return $result;
    }

    /**
     * Returns true if the given $tableName exists, otherwise false
     *
     * @param string $tableName
     * @return bool
     */
    private function tableExists(string $tableName): bool
    {
        try {
            $this->databaseHandle->prepare('SELECT 1 FROM "' . $tableName . '" LIMIT 1')->execute();
        } catch (\PDOException $exception) {
            return false;
        }
        return true;
    }
}
