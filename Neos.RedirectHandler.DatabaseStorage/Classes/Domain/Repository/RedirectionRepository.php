<?php
namespace Neos\RedirectHandler\DatabaseStorage\Domain\Repository;

/*
 * This file is part of the Neos.RedirectHandler package.
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
use Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirection;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Flow\Persistence\Repository;
use TYPO3\Neos\Domain\Service\DomainMatchingStrategy;

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
     * @Flow\Inject
     * @var DomainMatchingStrategy
     */
    protected $domainMatchingStrategy;

    /**
     * @var array
     */
    protected $defaultOrderings = array(
        'sourceUriPath' => QueryInterface::ORDER_ASCENDING
    );

    /**
     * @param string $sourceUriPath
     * @param string $host Full qualified hostname or host pattern
     * @return Redirection
     */
    public function findOneBySourceUriPathAndHost($sourceUriPath, $host = null)
    {
        $hostPattern = $this->hostPatternByHost($host);
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('sourceUriPathHash', md5(trim($sourceUriPath, '/'))),
                $query->equals('hostPattern', $hostPattern)
            )
        );

        return $query->execute()->getFirst();
    }

    /**
     * @param string $targetUriPath
     * @param string $host Full qualified hostname or host pattern
     * @return Redirection
     */
    public function findOneByTargetUriPathAndHost($targetUriPath, $host = null)
    {
        $hostPattern = $this->hostPatternByHost($host);
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('targetUriPathHash', md5(trim($targetUriPath, '/'))),
                $query->equals('hostPattern', $hostPattern)
            )
        );

        return $query->execute()->getFirst();
    }

    /**
     * Finds all objects and return an IterableResult
     *
     * @param string $host Full qualified hostname or host pattern
     * @param callable $callback
     * @return \Generator<Redirection>
     */
    public function findAll($host = null, callable $callback = null)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $query = $queryBuilder
            ->select('r')
            ->from($this->getEntityClassName(), 'r');

        if ($host !== null) {
            $hostPattern = $this->hostPatternByHost($host);
            $query->andWhere('r.hostPattern = :hostPattern')
                ->setParameter('hostPattern', $hostPattern);
        }

        $query->orderBy('r.hostPattern', 'ASC');
        $query->addOrderBy('r.sourceUriPath', 'ASC');

        return $this->iterate($query->getQuery()->iterate(), $callback);
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
            yield $object;
            if ($callback !== null) {
                call_user_func($callback, $iteration, $object);
            }
            ++$iteration;
        }
    }

    /**
     * @param string $host
     * @return array
     */
    protected function hostPatternByHost($host)
    {
        /** @var Query $query */
        $query = $this->entityManager->createQuery('SELECT DISTINCT r.hostPattern FROM Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirection r');
        $domains = array_filter(array_map(function($record) {
            return $record['hostPattern'];
        }, $query->getResult()));
        $matches = $this->domainMatchingStrategy->getSortedMatches($host, $domains);
        return reset($matches);
    }
}
