<?php
namespace TYPO3\Flow\Mvc\Routing;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
class ObjectPathMappingRepository extends Repository
{
    /**
     * @var string
     */
    const ENTITY_CLASSNAME = \TYPO3\Flow\Mvc\Routing\ObjectPathMapping::class;

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
    public function findOneByObjectTypeUriPatternAndPathSegment($objectType, $uriPattern, $pathSegment, $caseSensitive = false)
    {
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
    public function findOneByObjectTypeUriPatternAndIdentifier($objectType, $uriPattern, $identifier)
    {
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
    public function persistEntities()
    {
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
