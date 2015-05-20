<?php
namespace TYPO3\Flow\Resource;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\QueryBuilder;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Persistence\QueryResultInterface;
use TYPO3\Flow\Persistence\Repository;

/**
 * Resource Repository
 *
 * Note that this repository is not part of the public API and must not be used in client code. Please use the API
 * provided by Resource Manager instead.
 *
 * @Flow\Scope("singleton")
 * @see \TYPO3\Flow\Resource\ResourceManager
 */
class ResourceRepository extends Repository
{
    /**
     * @var string
     */
    const ENTITY_CLASSNAME = Resource::class;

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
     * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
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
     * Removes a Resource object from this repository
     *
     * @param object $object
     * @return void
     */
    public function remove($object)
    {
        // Intercept a second call for the same Resource object because it might cause an endless loop caused by
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
     * Iterator over an IterableResult and return a Generator
     *
     * This methos is useful for batch processing huge result set as it clear the object
     * manager and detach the current object on each iteration.
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
            ++$iteration;
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
            ->select('Resource')
            ->from($this->getEntityClassName(), 'Resource')
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
            ->select('Resource')
            ->from($this->getEntityClassName(), 'Resource')
            ->where('Resource.collectionName = :collectionName')
            ->setParameter(':collectionName', $collectionName)
            ->getQuery()->iterate();
    }

    /**
     * Finds other resources which are referring to the same resource data and filename
     *
     * @param Resource $resource The resource used for finding similar resources
     * @return QueryResultInterface The result, including the given resource
     */
    public function findSimilarResources(Resource $resource)
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
     * @return Resource
     */
    public function findOneBySha1($sha1Hash)
    {
        $query = $this->createQuery();
        $query->matching($query->equals('sha1', $sha1Hash))->setLimit(1);
        $resource = $query->execute()->getFirst();
        if ($resource === null) {
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
