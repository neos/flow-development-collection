<?php
namespace Neos\RedirectHandler\DatabaseStorage\Domain\Repository;

/*
 * This file is part of the Neos.RedirectHandler.DatabaseStorage package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirect;
use Neos\RedirectHandler\RedirectInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Flow\Persistence\Repository;

/**
 * Repository for redirect instances.
 * Note: You should not interact with this repository directly. Instead use the RedirectService!
 *
 * @Flow\Scope("singleton")
 */
class RedirectRepository extends Repository
{
    /**
     * @Flow\Inject
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $defaultOrderings = [
        'sourceUriPath' => QueryInterface::ORDER_ASCENDING,
        'host' => QueryInterface::ORDER_DESCENDING
    ];

    /**
     * @param string $sourceUriPath
     * @param string $host Full qualified host name
     * @param boolean $fallback If not redirect found, match a redirect with host value as null
     * @return Redirect
     */
    public function findOneBySourceUriPathAndHost($sourceUriPath, $host = null, $fallback = true)
    {
        $query = $this->createQuery();

        if ($fallback === true) {
            $constraints = $query->logicalAnd(
                $query->equals('sourceUriPathHash', md5(trim($sourceUriPath, '/'))),
                $query->logicalOr(
                    $query->equals('host', $host),
                    $query->equals('host', null)
                )
            );
        } else {
            $constraints = $query->logicalAnd(
                $query->equals('sourceUriPathHash', md5(trim($sourceUriPath, '/'))),
                $query->equals('host', $host)
            );
        }

        $query->matching($constraints);

        return $query->execute()->getFirst();
    }

    /**
     * @param string $targetUriPath
     * @param string $host Full qualified host name
     * @return Redirect
     */
    public function findOneByTargetUriPathAndHost($targetUriPath, $host = null)
    {
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('targetUriPathHash', md5(trim($targetUriPath, '/'))),
                $query->logicalOr(
                    $query->equals('host', $host),
                    $query->equals('host', null)
                )
            )
        );

        return $query->execute()->getFirst();
    }

    /**
     * @param string $targetUriPath
     * @param string $host Full qualified host name
     * @return QueryInterface
     */
    public function findByTargetUriPathAndHost($targetUriPath, $host = null)
    {
        /** @var Query $query */
        $query = $this->entityManager->createQuery('SELECT r FROM Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirect r WHERE r.targetUriPathHash = :targetUriPathHash AND (r.host = :host OR r.host IS NULL)');
        $query->setParameter('targetUriPathHash', md5(trim($targetUriPath, '/')));
        $query->setParameter('host', $host);

        return $this->iterate($query->iterate());
    }

    /**
     * Finds all objects and return an IterableResult
     *
     * @param string $host Full qualified host name
     * @param callable $callback
     * @return \Generator<Redirect>
     */
    public function findAll($host = null, callable $callback = null)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $query = $queryBuilder
            ->select('r')
            ->from($this->getEntityClassName(), 'r');

        if ($host !== null) {
            $query->andWhere('r.host = :host')
                ->setParameter('host', $host);
        } else {
            $query->andWhere('r.host IS NULL');
        }

        $query->orderBy('r.host', 'ASC');
        $query->addOrderBy('r.sourceUriPath', 'ASC');

        return $this->iterate($query->getQuery()->iterate(), $callback);
    }

    /**
     * @return void
     */
    public function removeAll()
    {
        /** @var Query $query */
        $query = $this->entityManager->createQuery('DELETE Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirect r');
        $query->execute();
    }

    /**
     * @param string|null $host
     * @return void
     */
    public function removeByHost($host = null)
    {
        /** @var Query $query */
        if ($host === null) {
            $query = $this->entityManager->createQuery('DELETE Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirect r WHERE r.host IS NULL');
        } else {
            $query = $this->entityManager->createQuery('DELETE Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirect r WHERE r.host = :host');
            $query->setParameter(':host', $host);
        }
        $query->execute();
    }

    /**
     * Return a list of all hosts
     *
     * @return array
     */
    public function findDistinctHosts()
    {
        /** @var Query $query */
        $query = $this->entityManager->createQuery('SELECT DISTINCT r.host FROM Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirect r');
        return array_map(function ($record) {
            return $record['host'];
        }, $query->getResult());
    }

    /**
     * @param RedirectInterface $redirect
     * @return void
     */
    public function incrementHitCount(RedirectInterface $redirect)
    {
        $host = $redirect->getHost();
        /** @var Query $query */
        if ($host === null) {
            $query = $this->entityManager->createQuery('UPDATE Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirect r SET r.hitCounter = r.hitCounter + 1 WHERE r.sourceUriPath = :sourceUriPath and r.host IS NULL');
        } else {
            $query = $this->entityManager->createQuery('UPDATE Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirect r SET r.hitCounter = r.hitCounter + 1 WHERE r.sourceUriPath = :sourceUriPath and r.host = :host');
            $query->setParameter('host', $host);
        }
        $query->setParameter('sourceUriPath', $redirect->getSourceUriPath())
            ->execute();
    }

    /**
     * Iterator over an IterableResult and return a Generator
     *
     * @param IterableResult $iterator
     * @param callable $callback
     * @return \Generator<RedirectDto>
     */
    protected function iterate(IterableResult $iterator, callable $callback = null)
    {
        $iteration = 0;
        foreach ($iterator as $object) {
            /** @var Redirect $object */
            $object = current($object);
            yield $object;
            if ($callback !== null) {
                call_user_func($callback, $iteration, $object);
            }
            $iteration++;
        }
    }
}
