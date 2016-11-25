<?php
namespace Neos\Flow\Persistence\Generic\Backend;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Persistence\QueryInterface;

/**
 * A persistence backend interface
 *
 * @api
 */
interface BackendInterface
{
    /**
     * Set a PersistenceManager instance.
     *
     * @param PersistenceManagerInterface $persistenceManager
     * @return void
     */
    public function setPersistenceManager(PersistenceManagerInterface $persistenceManager);

    /**
     * Initializes the backend
     *
     * @param array $options
     * @return void
     * @api
     */
    public function initialize(array $options);

    /**
     * Sets the aggregate root objects
     *
     * @param \SplObjectStorage $objects
     * @return void
     * @api
     */
    public function setAggregateRootObjects(\SplObjectStorage $objects);

    /**
     * Sets the deleted entities
     *
     * @param \SplObjectStorage $entities
     * @return void
     * @api
     */
    public function setDeletedEntities(\SplObjectStorage $entities);

    /**
     * Sets the changed objects
     *
     * @param \SplObjectStorage $entities
     * @return void
     */
    public function setChangedEntities(\SplObjectStorage $entities);

    /**
     * Commits the current persistence session
     *
     * @return void
     * @api
     */
    public function commit();

    /**
     * Returns the number of items matching the query.
     *
     * @param QueryInterface $query
     * @return integer
     * @api
     */
    public function getObjectCountByQuery(QueryInterface $query);

    /**
     * Returns the object data matching the $query.
     *
     * @param QueryInterface $query
     * @return array
     * @api
     */
    public function getObjectDataByQuery(QueryInterface $query);

    /**
     * Returns the object data for the given identifier.
     *
     * @param string $identifier The UUID or Hash of the object
     * @param string $objectType
     * @return array
     * @api
     */
    public function getObjectDataByIdentifier($identifier, $objectType = null);

    /**
     * Returns TRUE, if an active connection to the persistence
     * backend has been established, e.g. entities can be persisted.
     *
     * @return boolean TRUE, if an connection has been established, FALSE if add object will not be persisted by the backend
     * @api
     */
    public function isConnected();
}
