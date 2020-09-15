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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Persistence\AbstractPersistenceManager;
use Neos\Flow\Persistence\Exception\KnownObjectException;
use Neos\Flow\Persistence\Exception as PersistenceException;
use Neos\Flow\Persistence\Exception\UnknownObjectException;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Validation\ValidatorResolver;
use Psr\Log\LoggerInterface;

/**
 * Flow's Doctrine PersistenceManager
 *
 * @Flow\Scope("singleton")
 * @api
 */
class PersistenceManager extends AbstractPersistenceManager
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var ThrowableStorageInterface
     */
    protected $throwableStorage;

    /**
     * @Flow\Inject(lazy=false)
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @Flow\Inject
     * @var ValidatorResolver
     */
    protected $validatorResolver;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * Injects the (system) logger based on PSR-3.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function injectLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Commits new objects and changes to objects in the current persistence
     * session into the backend
     *
     * @param boolean $onlyWhitelistedObjects If true an exception will be thrown if there are scheduled updates/deletes or insertions for objects that are not "whitelisted" (see AbstractPersistenceManager::whitelistObject())
     * @return void
     * @api
     * @throws PersistenceException
     */
    public function persistAll($onlyWhitelistedObjects = false)
    {
        if ($onlyWhitelistedObjects) {
            $unitOfWork = $this->entityManager->getUnitOfWork();
            /** @var \Doctrine\ORM\UnitOfWork $unitOfWork */
            $unitOfWork->computeChangeSets();
            $objectsToBePersisted = $unitOfWork->getScheduledEntityUpdates() + $unitOfWork->getScheduledEntityDeletions() + $unitOfWork->getScheduledEntityInsertions();
            foreach ($objectsToBePersisted as $object) {
                $this->throwExceptionIfObjectIsNotWhitelisted($object);
            }
        }

        if (!$this->entityManager->isOpen()) {
            $this->logger->error('persistAll() skipped flushing data, the Doctrine EntityManager is closed. Check the logs for error message.', LogEnvironment::fromMethodName(__METHOD__));
            return;
        }

        /** @var Connection $connection */
        $connection = $this->entityManager->getConnection();
        try {
            if ($connection->ping() === false) {
                $this->logger->info('Reconnecting the Doctrine EntityManager to the persistence backend.', LogEnvironment::fromMethodName(__METHOD__));
                $connection->close();
                $connection->connect();
            }
        } catch (ConnectionException $exception) {
            $message = $this->throwableStorage->logThrowable($exception);
            $this->logger->error($message, LogEnvironment::fromMethodName(__METHOD__));
        }

        $this->entityManager->flush();
        $this->emitAllObjectsPersisted();
    }

    /**
     * Clears the in-memory state of the persistence.
     *
     * Managed instances become detached, any fetches will
     * return data directly from the persistence "backend".
     *
     * @return void
     */
    public function clearState()
    {
        parent::clearState();
        $this->entityManager->clear();
    }

    /**
     * Checks if the given object has ever been persisted.
     *
     * @param object $object The object to check
     * @return boolean true if the object is new, false if the object exists in the repository
     * @api
     */
    public function isNewObject($object)
    {
        return ($this->entityManager->getUnitOfWork()->getEntityState($object, \Doctrine\ORM\UnitOfWork::STATE_NEW) === \Doctrine\ORM\UnitOfWork::STATE_NEW);
    }

    /**
     * Returns the (internal) identifier for the object, if it is known to the
     * backend. Otherwise NULL is returned.
     *
     * Note: this returns an identifier even if the object has not been
     * persisted in case of AOP-managed entities. Use isNewObject() if you need
     * to distinguish those cases.
     *
     * @param object $object
     * @return mixed The identifier for the object if it is known, or NULL
     * @api
     * @todo improve try/catch block
     */
    public function getIdentifierByObject($object)
    {
        if (property_exists($object, 'Persistence_Object_Identifier')) {
            $identifierCandidate = ObjectAccess::getProperty($object, 'Persistence_Object_Identifier', true);
            if ($identifierCandidate !== null) {
                return $identifierCandidate;
            }
        }
        if ($this->entityManager->contains($object)) {
            try {
                return current($this->entityManager->getUnitOfWork()->getEntityIdentifier($object));
            } catch (\Doctrine\ORM\ORMException $exception) {
            }
        }
        return null;
    }

    /**
     * Returns the object with the (internal) identifier, if it is known to the
     * backend. Otherwise NULL is returned.
     *
     * @param mixed $identifier
     * @param string $objectType
     * @param boolean $useLazyLoading Set to true if you want to use lazy loading for this object
     * @return object The object for the identifier if it is known, or NULL
     * @throws \RuntimeException
     * @api
     */
    public function getObjectByIdentifier($identifier, $objectType = null, $useLazyLoading = false)
    {
        if ($objectType === null) {
            throw new \RuntimeException('Using only the identifier is not supported by Doctrine 2. Give classname as well or use repository to query identifier.', 1296646103);
        }
        if (isset($this->newObjects[$identifier])) {
            return $this->newObjects[$identifier];
        }
        if ($useLazyLoading === true) {
            return $this->entityManager->getReference($objectType, $identifier);
        } else {
            return $this->entityManager->find($objectType, $identifier);
        }
    }

    /**
     * Return a query object for the given type.
     *
     * @param string $type
     * @return Query
     */
    public function createQueryForType($type)
    {
        return new Query($type);
    }

    /**
     * Adds an object to the persistence.
     *
     * @param object $object The object to add
     * @return void
     * @throws KnownObjectException if the given $object is not new
     * @throws PersistenceException if another error occurs
     * @api
     */
    public function add($object)
    {
        if (!$this->isNewObject($object)) {
            throw new KnownObjectException('The object of type "' . get_class($object) . '" (identifier: "' . $this->getIdentifierByObject($object) . '") which was passed to EntityManager->add() is not a new object. Check the code which adds this entity to the repository and make sure that only objects are added which were not persisted before. Alternatively use update() for updating existing objects."', 1337934295);
        } else {
            try {
                $this->entityManager->persist($object);
            } catch (\Exception $exception) {
                throw new PersistenceException('Could not add object of type "' . get_class($object) . '"', 1337934455, $exception);
            }
        }
    }

    /**
     * Removes an object to the persistence.
     *
     * @param object $object The object to remove
     * @return void
     * @api
     */
    public function remove($object)
    {
        $this->entityManager->remove($object);
    }

    /**
     * Update an object in the persistence.
     *
     * @param object $object The modified object
     * @return void
     * @throws UnknownObjectException if the given $object is new
     * @throws PersistenceException if another error occurs
     * @api
     */
    public function update($object)
    {
        if ($this->isNewObject($object)) {
            throw new UnknownObjectException('The object of type "' . get_class($object) . '" (identifier: "' . $this->getIdentifierByObject($object) . '") which was passed to EntityManager->update() is not a previously persisted object. Check the code which updates this entity and make sure that only objects are updated which were persisted before. Alternatively use add() for persisting new objects."', 1313663277);
        }
        try {
            $this->entityManager->persist($object);
        } catch (\Exception $exception) {
            throw new PersistenceException('Could not merge object of type "' . get_class($object) . '"', 1297778180, $exception);
        }
    }

    /**
     * Returns true, if an active connection to the persistence
     * backend has been established, e.g. entities can be persisted.
     *
     * @return boolean true, if an connection has been established, false if add object will not be persisted by the backend
     * @api
     */
    public function isConnected()
    {
        return $this->entityManager->getConnection()->isConnected();
    }

    /**
     * Called from functional tests, creates/updates database tables and compiles proxies.
     *
     * @return boolean
     */
    public function compile()
    {
        // "driver" is used only for Doctrine, thus we (mis-)use it here
        // additionally, when no path is set, skip this step, assuming no DB is needed
        if ($this->settings['backendOptions']['driver'] !== null && $this->settings['backendOptions']['path'] !== null) {
            $schemaTool = new SchemaTool($this->entityManager);
            if ($this->settings['backendOptions']['driver'] === 'pdo_sqlite') {
                $schemaTool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
            } else {
                $schemaTool->updateSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
            }

            $proxyFactory = $this->entityManager->getProxyFactory();
            $proxyFactory->generateProxyClasses($this->entityManager->getMetadataFactory()->getAllMetadata());

            $this->logger->info('Doctrine 2 setup finished', LogEnvironment::fromMethodName(__METHOD__));
            return true;
        } else {
            $this->logger->notice('Doctrine 2 setup skipped, driver and path backend options not set!');
            return false;
        }
    }

    /**
     * Called after a functional test in Flow, dumps everything in the database.
     *
     * @return void
     */
    public function tearDown()
    {
        // "driver" is used only for Doctrine, thus we (mis-)use it here
        // additionally, when no path is set, skip this step, assuming no DB is needed
        if ($this->settings['backendOptions']['driver'] !== null && $this->settings['backendOptions']['path'] !== null) {
            $this->entityManager->clear();

            $schemaTool = new SchemaTool($this->entityManager);
            $schemaTool->dropDatabase();
            $this->logger->notice('Doctrine 2 schema destroyed.');
        } else {
            $this->logger->notice('Doctrine 2 destroy skipped, driver and path backend options not set!');
        }
    }

    /**
     * Signals that all persistAll() has been executed successfully.
     *
     * @Flow\Signal
     * @return void
     */
    protected function emitAllObjectsPersisted()
    {
    }

    /**
     * Gives feedback if the persistence Manager has unpersisted changes.
     *
     * This is primarily used to inform the user if he tries to save
     * data in an unsafe request.
     *
     * @return boolean
     */
    public function hasUnpersistedChanges()
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSets();

        if ($unitOfWork->getScheduledEntityInsertions() !== []
            || $unitOfWork->getScheduledEntityUpdates() !== []
            || $unitOfWork->getScheduledEntityDeletions() !== []
            || $unitOfWork->getScheduledCollectionDeletions() !== []
            || $unitOfWork->getScheduledCollectionUpdates() !== []
        ) {
            return true;
        }

        return false;
    }
}
