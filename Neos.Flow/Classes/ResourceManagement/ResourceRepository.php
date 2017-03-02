<?php
namespace Neos\Flow\ResourceManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\QueryBuilder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Persistence\Repository;

/**
 * PersistentResource Repository
 *
 * Note that this repository is not part of the public API and must not be used in client code. Please use the API
 * provided by ResourceManager instead.
 *
 * @Flow\Scope("singleton")
 * @see ResourceManager
 */
class ResourceRepository extends Repository
{
    /**
     * @var string
     */
    const ENTITY_CLASSNAME = PersistentResource::class;

    /**
     * @Flow\Inject
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var \SplObjectStorage
     */
    protected $removedResources;

    /**
     * @var \SplObjectStorage
     */
    protected $addedResources;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->removedResources = new \SplObjectStorage();
        $this->addedResources = new \SplObjectStorage();
    }

    /**
     * @param object $object
     * @throws IllegalObjectTypeException
     */
    public function add($object)
    {
        $this->persistenceManager->whitelistObject($object);
        if ($this->removedResources->contains($object)) {
            $this->removedResources->detach($object);
        }
        if (!$this->addedResources->contains($object)) {
            $this->addedResources->attach($object);
            parent::add($object);
        }
    }

    /**
     * Removes a PersistentResource object from this repository
     *
     * @param object $object
     * @return void
     */
    public function remove($object)
    {
        // Intercept a second call for the same PersistentResource object because it might cause an endless loop caused by
        // the ResourceManager's deleteResource() method which also calls this remove() function:
        if (!$this->removedResources->contains($object)) {
            $this->removedResources->attach($object);
            parent::remove($object);
        }
    }

    /**
     * Finds an object matching the given identifier.
     *
     * @param mixed $identifier The identifier of the object to find
     * @return object The matching object if found, otherwise NULL
     * @api
     */
    public function findByIdentifier($identifier)
    {
        $object = $this->persistenceManager->getObjectByIdentifier($identifier, $this->entityClassName);
        if ($object === null) {
            foreach ($this->addedResources as $addedResource) {
                if ($this->persistenceManager->getIdentifierByObject($addedResource) === $identifier) {
                    $object = $addedResource;
                    break;
                }
            }
        }

        return $object;
    }

    /**
     * Allow to iterate on an IterableResult and return a Generator
     *
     * This methos is useful for batch processing huge result set. The callback
     * is executed after every iteration. It can be used to clear the state of
     * the persistence layer.
     *
     * @param IterableResult $iterator
     * @param callable $callback
     * @return \Generator
     */
    public function iterate(IterableResult $iterator, callable $callback = null)
    {
        $iteration = 0;
        foreach ($iterator as $object) {
            $object = current($object);
            yield $object;
            if ($callback !== null) {
                call_user_func($callback, $iteration, $object);
            }
            $iteration++;
        }
    }

    /**
     * Finds all objects and return an IterableResult
     *
     * @return IterableResult
     */
    public function findAllIterator()
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();
        return $queryBuilder
            ->select('PersistentResource')
            ->from($this->getEntityClassName(), 'PersistentResource')
            ->getQuery()->iterate();
    }

    /**
     * Finds all objects by collection name and return an IterableResult
     *
     * @param string $collectionName
     * @return IterableResult
     */
    public function findByCollectionNameIterator($collectionName)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();
        return $queryBuilder
            ->select('PersistentResource')
            ->from($this->getEntityClassName(), 'PersistentResource')
            ->where('PersistentResource.collectionName = :collectionName')
            ->setParameter(':collectionName', $collectionName)
            ->getQuery()->iterate();
    }

    /**
     * Finds other resources which are referring to the same resource data and filename
     *
     * @param PersistentResource $resource The resource used for finding similar resources
     * @return QueryResultInterface The result, including the given resource
     */
    public function findSimilarResources(PersistentResource $resource)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('sha1', $resource->getSha1()),
                $query->equals('filename', $resource->getFilename())
            )
        );
        return $query->execute();
    }

    /**
     * Find all resources with the same SHA1 hash
     *
     * @param string $sha1Hash
     * @return array
     */
    public function findBySha1($sha1Hash)
    {
        $query = $this->createQuery();
        $query->matching($query->equals('sha1', $sha1Hash));
        $resources = $query->execute()->toArray();
        foreach ($this->addedResources as $importedResource) {
            if ($importedResource->getSha1() === $sha1Hash) {
                $resources[] = $importedResource;
            }
        }

        return $resources;
    }

    /**
     * Find one resource by SHA1
     *
     * @param string $sha1Hash
     * @return PersistentResource
     */
    public function findOneBySha1($sha1Hash)
    {
        $query = $this->createQuery();
        $query->matching($query->equals('sha1', $sha1Hash))->setLimit(1);
        /** @var PersistentResource $resource */
        $resource = $query->execute()->getFirst();
        if ($resource === null) {
            /** @var PersistentResource $importedResource */
            foreach ($this->addedResources as $importedResource) {
                if ($importedResource->getSha1() === $sha1Hash) {
                    return $importedResource;
                }
            }
        }

        return $resource;
    }

    /**
     * @return \SplObjectStorage
     */
    public function getAddedResources()
    {
        return clone $this->addedResources;
    }
}
