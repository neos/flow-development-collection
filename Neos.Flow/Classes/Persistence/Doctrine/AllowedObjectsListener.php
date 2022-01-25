<?php
declare(strict_types=1);

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

use Doctrine\ORM\Event\OnFlushEventArgs;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\AllowedObjectsContainer;
use Neos\Flow\Persistence\Exception as PersistenceException;
use Neos\Flow\Persistence\PersistenceManagerInterface;

/**
 * An onFlush listener for Flow's Doctrine PersistenceManager, that validates to be persisted entities
 * against the list of allowed objects.
 *
 * This listener is outsourced from the PersistenceManager to avoid recursive dependencies when building
 * the EntityManager.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class AllowedObjectsListener
{
    /**
     * @Flow\Inject
     * @var AllowedObjectsContainer
     */
    protected $allowedObjects;

    /**
     * @Flow\Inject(lazy=true)
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Doctrine onFlush listener that checks for only allowed objects.
     *
     * @param OnFlushEventArgs $args
     * @throws PersistenceException
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $unitOfWork = $args->getEntityManager()->getUnitOfWork();
        if ($unitOfWork->getScheduledEntityInsertions() === []
            && $unitOfWork->getScheduledEntityUpdates() === []
            && $unitOfWork->getScheduledEntityDeletions() === []
            && $unitOfWork->getScheduledCollectionDeletions() === []
            && $unitOfWork->getScheduledCollectionUpdates() === []
        ) {
            $this->allowedObjects->checkNext(false);
            return;
        }

        if ($this->allowedObjects->shouldCheck()) {
            $objectsToBePersisted = $unitOfWork->getScheduledEntityUpdates() + $unitOfWork->getScheduledEntityDeletions() + $unitOfWork->getScheduledEntityInsertions();
            foreach ($objectsToBePersisted as $object) {
                $this->throwExceptionIfObjectIsNotAllowed($object);
            }
        }
    }

    /**
     * Checks if the given object is allowed and if not, throws an exception
     *
     * @param object $object
     * @return void
     * @throws \Neos\Flow\Persistence\Exception
     */
    protected function throwExceptionIfObjectIsNotAllowed($object)
    {
        if (!$this->allowedObjects->contains($object)) {
            $message = 'Detected modified or new objects (' . get_class($object) . ', uuid:' . $this->persistenceManager->getIdentifierByObject($object) . ') to be persisted which is not allowed for "safe requests"' . chr(10) .
                'According to the HTTP 1.1 specification, so called "safe request" (usually GET or HEAD requests)' . chr(10) .
                'should not change your data on the server side and should be considered read-only. If you need to add,' . chr(10) .
                'modify or remove data, you should use the respective request methods (POST, PUT, DELETE and PATCH).' . chr(10) . chr(10) .
                'If you need to store some data during a safe request (for example, logging some data for your analytics),' . chr(10) .
                'you are still free to call PersistenceManager->persistAll() manually.';
            throw new PersistenceException($message, 1377788621);
        }
    }
}
