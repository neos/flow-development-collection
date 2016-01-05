<?php
namespace TYPO3\Flow\Http\Redirection\Storage;

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
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\QueryBuilder;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Flow\Http\Redirection\Redirection as RedirectionDto;
use TYPO3\Flow\Persistence\Repository;

/**
 * Repository for redirection instances.
 * Note: You should not interact with this repository directly. Instead use the RedirectionService!
 *
 * @Flow\Scope("singleton")
 */
class RedirectionRepository extends Repository
{
    /**
     * @Flow\Inject
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $defaultOrderings = array(
        'sourceUriPath' => QueryInterface::ORDER_ASCENDING,
    );

    /**
     * @param string $sourceUriPath
     * @return Redirection
     */
    public function findOneBySourceUriPath($sourceUriPath)
    {
        $query = $this->createQuery();

        $query->matching($query->equals('sourceUriPathHash', md5(trim($sourceUriPath, '/'))));

        return $query->execute()->getFirst();
    }

    /**
     * @param string $targetUriPath
     * @return Redirection
     */
    public function findOneByTargetUriPath($targetUriPath)
    {
        $query = $this->createQuery();

        $query->matching($query->equals('targetUriPathHash', md5(trim($targetUriPath, '/'))));

        return $query->execute()->getFirst();
    }

    /**
     * Finds all objects and return an IterableResult
     *
     * @param callable $callback
     * @return \Generator<RedirectionDto>
     */
    public function findAll(callable $callback = null)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();
        return $this->iterate($queryBuilder
            ->select('Redirection')
            ->from($this->getEntityClassName(), 'Redirection')
            ->getQuery()->iterate(), $callback);
    }

    /**
     * Iterator over an IterableResult and return a Generator
     *
     * @param IterableResult $iterator
     * @param callable $callback
     * @return \Generator<RedirectionDto>
     */
    protected function iterate(IterableResult $iterator, callable $callback = null)
    {
        $iteration = 0;
        foreach ($iterator as $object) {
            /** @var Redirection $object */
            $object = current($object);
            yield new RedirectionDto($object->getSourceUriPath(), $object->getTargetUriPath(), $object->getStatusCode());
            if ($callback !== null) {
                call_user_func($callback, $iteration, $object);
            }
            ++$iteration;
        }
    }
}
