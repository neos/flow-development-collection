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

use TYPO3\Flow\Annotations as Flow;
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
class ResourceRepository extends Repository {

	/**
	 * @var string
	 */
	const ENTITY_CLASSNAME = 'TYPO3\Flow\Resource\Resource';

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
	public function __construct() {
		parent::__construct();
		$this->removedResources = new \SplObjectStorage();
		$this->addedResources = new \SplObjectStorage();
	}

	/**
	 * @param object $object
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 */
	public function add($object) {
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
	public function remove($object) {
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
	public function findByIdentifier($identifier) {
		$object = $this->persistenceManager->getObjectByIdentifier($identifier, $this->entityClassName);
		if ($object === NULL) {
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
	 * Finds other resources which are referring to the same resource data and filename
	 *
	 * @param Resource $resource The resource used for finding similar resources
	 * @return QueryResultInterface The result, including the given resource
	 */
	public function findSimilarResources(Resource $resource) {
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
	public function findBySha1($sha1Hash) {
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
	public function findOneBySha1($sha1Hash) {
		$query = $this->createQuery();
		$query->matching($query->equals('sha1', $sha1Hash))->setLimit(1);
		$resource = $query->execute()->getFirst();
		if ($resource === NULL) {
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
	public function getAddedResources() {
		return clone $this->addedResources;
	}

}
