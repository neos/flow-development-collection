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

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Persistence\RepositoryInterface;

/**
 * The Flow default Repository, based on Doctrine 2
 *
 * @api
 */
abstract class Repository extends EntityRepository implements RepositoryInterface
{
    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * Warning: if you think you want to set this,
     * look at RepositoryInterface::ENTITY_CLASSNAME first!
     *
     * @var string
     */
    protected $objectType;

    /**
     * @var array
     */
    protected $defaultOrderings = [];

    /**
     * Initializes a new Repository.
     *
     * @param EntityManagerInterface $entityManager The EntityManager to use.
     * @param ClassMetadata $classMetadata The class descriptor.
     */
    public function __construct(EntityManagerInterface $entityManager, ClassMetadata $classMetadata = null)
    {
        if ($classMetadata === null) {
            if (defined('static::ENTITY_CLASSNAME') === false) {
                $this->objectType = preg_replace(['/\\\Repository\\\/', '/Repository$/'], ['\\Model\\', ''], get_class($this));
            } else {
                $this->objectType = static::ENTITY_CLASSNAME;
            }
            $classMetadata = $entityManager->getClassMetadata($this->objectType);
        }
        parent::__construct($entityManager, $classMetadata);
        $this->entityManager = $this->_em;
    }

    /**
     * Returns the classname of the entities this repository is managing.
     *
     * @return string
     * @api
     */
    public function getEntityClassName()
    {
        return $this->objectType;
    }

    /**
     * Adds an object to this repository.
     *
     * @param object $object The object to add
     * @return void
     * @throws IllegalObjectTypeException
     * @api
     */
    public function add($object)
    {
        if (!is_object($object) || !($object instanceof $this->objectType)) {
            $type = (is_object($object) ? get_class($object) : gettype($object));
            throw new IllegalObjectTypeException('The value given to add() was ' . $type . ' , however the ' . get_class($this) . ' can only store ' . $this->objectType . ' instances.', 1517408062);
        }
        $this->entityManager->persist($object);
    }

    /**
     * Removes an object from this repository.
     *
     * @param object $object The object to remove
     * @return void
     * @throws IllegalObjectTypeException
     * @api
     */
    public function remove($object)
    {
        if (!is_object($object) || !($object instanceof $this->objectType)) {
            $type = (is_object($object) ? get_class($object) : gettype($object));
            throw new IllegalObjectTypeException('The value given to remove() was ' . $type . ' , however the ' . get_class($this) . ' can only handle ' . $this->objectType . ' instances.', 1517408067);
        }
        $this->entityManager->remove($object);
    }

    /**
     * Finds all entities in the repository.
     *
     * @return \Neos\Flow\Persistence\QueryResultInterface The query result
     * @api
     */
    public function findAll()
    {
        return $this->createQuery()->execute();
    }

    /**
     * Find all objects and return an IterableResult
     *
     * @return IterableResult
     */
    public function findAllIterator()
    {
        /** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->createQueryBuilder();
        return $queryBuilder
            ->select('entity')
            ->from($this->getEntityClassName(), 'entity')
            ->getQuery()->iterate();
    }

    /**
     * Iterate over an IterableResult and return a Generator
     *
     * This method is useful for batch processing a huge result set.
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

            $iteration++;
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
        return $this->entityManager->find($this->objectType, $identifier);
    }

    /**
     * Returns a query for objects of this repository
     *
     * @return Query
     * @api
     */
    public function createQuery()
    {
        $query = new Query($this->objectType);
        if ($this->defaultOrderings) {
            $query->setOrderings($this->defaultOrderings);
        }
        return $query;
    }

    /**
     * Creates a DQL query from the given query string
     *
     * @param string $dqlString The query string
     * @return \Doctrine\ORM\Query The DQL query object
     */
    public function createDqlQuery($dqlString)
    {
        $dqlQuery = $this->entityManager->createQuery($dqlString);
        return $dqlQuery;
    }

    /**
     * Counts all objects of this repository
     *
     * @return integer
     * @api
     */
    public function countAll()
    {
        return $this->createQuery()->count();
    }

    /**
     * Removes all objects of this repository as if remove() was called for
     * all of them.
     *
     * @return void
     * @api
     * @todo maybe use DQL here, would be much more performant
     */
    public function removeAll()
    {
        foreach ($this->findAll() as $object) {
            $this->remove($object);
        }
    }

    /**
     * Sets the property names to order results by. Expected like this:
     * array(
     *  'foo' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
     *  'bar' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING
     * )
     *
     * @param array $defaultOrderings The property names to order by by default
     * @return void
     * @api
     */
    public function setDefaultOrderings(array $defaultOrderings)
    {
        $this->defaultOrderings = $defaultOrderings;
    }

    /**
     * Schedules a modified object for persistence.
     *
     * @param object $object The modified object
     * @return void
     * @throws IllegalObjectTypeException
     * @api
     */
    public function update($object)
    {
        if (!($object instanceof $this->objectType)) {
            throw new IllegalObjectTypeException('The modified object given to update() was not of the type (' . $this->objectType . ') this repository manages.', 1249479625);
        }
        $this->persistenceManager->update($object);
    }

    /**
     * Magic call method for repository methods.
     *
     * Provides three methods
     *  - findBy<PropertyName>($value, $caseSensitive = true, $cacheResult = false)
     *  - findOneBy<PropertyName>($value, $caseSensitive = true, $cacheResult = false)
     *  - countBy<PropertyName>($value, $caseSensitive = true)
     *
     * @param string $method Name of the method
     * @param array $arguments The arguments
     * @return mixed The result of the repository method
     * @api
     */
    public function __call($method, $arguments)
    {
        $query = $this->createQuery();
        $caseSensitive = isset($arguments[1]) ? (boolean)$arguments[1] : true;
        $cacheResult = isset($arguments[2]) ? (boolean)$arguments[2] : false;

        if (isset($method[10]) && strpos($method, 'findOneBy') === 0) {
            $propertyName = lcfirst(substr($method, 9));
            return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->execute($cacheResult)->getFirst();
        } elseif (isset($method[8]) && strpos($method, 'countBy') === 0) {
            $propertyName = lcfirst(substr($method, 7));
            return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->count();
        } elseif (isset($method[7]) && strpos($method, 'findBy') === 0) {
            $propertyName = lcfirst(substr($method, 6));
            return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->execute($cacheResult);
        }

        trigger_error('Call to undefined method ' . get_class($this) . '::' . $method, E_USER_ERROR);
    }
}
