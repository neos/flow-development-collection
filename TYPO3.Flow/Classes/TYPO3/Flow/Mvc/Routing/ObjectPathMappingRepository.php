<?php
namespace TYPO3\Flow\Mvc\Routing;

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
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Flow\Persistence\Repository;

/**
 * Repository for object path mapping objects
 * @see \TYPO3\Flow\Mvc\Routing\ObjectPathMapping
 *
 * @Flow\Scope("singleton")
 */
class ObjectPathMappingRepository extends Repository {

	/**
	 * @var string
	 */
	const ENTITY_CLASSNAME = 'TYPO3\Flow\Mvc\Routing\ObjectPathMapping';

	/**
	 * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related interface.
	 *
	 * @Flow\Inject
	 * @var ObjectManager
	 */
	protected $entityManager;

	/**
	 * @var array
	 */
	protected $defaultOrderings = array(
		'objectType' => QueryInterface::ORDER_ASCENDING,
		'uriPattern' => QueryInterface::ORDER_ASCENDING
	);

	/**
	 * @param string $objectType the object type of the ObjectPathMapping object
	 * @param string $uriPattern the URI pattern of the ObjectPathMapping object
	 * @param string $pathSegment the URI path segment of the ObjectPathMapping object
	 * @param boolean $caseSensitive whether the path segment lookup should be done case-sensitive
	 * @return \TYPO3\Flow\Mvc\Routing\ObjectPathMapping
	 */
	public function findOneByObjectTypeUriPatternAndPathSegment($objectType, $uriPattern, $pathSegment, $caseSensitive = FALSE) {
		$query = $this->createQuery();
		return $query->matching(
			$query->logicalAnd(
				$query->equals('objectType', $objectType),
				$query->equals('uriPattern', $uriPattern),
				$query->equals('pathSegment', $pathSegment, $caseSensitive)
			)
		)
		->execute()
		->getFirst();
	}

	/**
	 * @param string $objectType the object type of the ObjectPathMapping object
	 * @param string $uriPattern the URI pattern of the ObjectPathMapping object
	 * @param string|integer $identifier the identifier of the object, for example the UUID, @see \TYPO3\Flow\Persistence\PersistenceManagerInterface::getIdentifierByObject()
	 * @return \TYPO3\Flow\Mvc\Routing\ObjectPathMapping
	 * @throws \InvalidArgumentException
	 */
	public function findOneByObjectTypeUriPatternAndIdentifier($objectType, $uriPattern, $identifier) {
		$query = $this->createQuery();
		return $query->matching(
			$query->logicalAnd(
				$query->equals('objectType', $objectType),
				$query->equals('uriPattern', $uriPattern),
				$query->equals('identifier', $identifier)
			)
		)
		->execute()
		->getFirst();
	}

	/**
	 * Persists all entities managed by the repository and all cascading dependencies
	 *
	 * @return void
	 */
	public function persistEntities() {
		foreach ($this->entityManager->getUnitOfWork()->getIdentityMap() as $className => $entities) {
			if ($className === $this->entityClassName) {
				foreach ($entities as $entityToPersist) {
					$this->entityManager->flush($entityToPersist);
				}
				return;
			}
		}
	}

}

