<?php
namespace Neos\Flow\Persistence\Doctrine;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Connection factory for Doctrine connection class
 *
 * @Flow\Scope("singleton")
 */
final class ConnectionFactory
{
    /**
     * @Flow\Inject
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * Factory method which creates an Connection from
     * the injected EntityManager.
     *
     * This ensure, that configured database and similar settings
     * from Neos.Flow.persistence.doctrine is registered as expected
     *
     * @return Connection
     */
    public function create(): Connection
    {
        return $this->entityManager->getConnection();
    }
}
