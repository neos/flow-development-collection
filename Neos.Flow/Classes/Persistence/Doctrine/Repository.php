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

use Doctrine\ORM\EntityRepository;
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
     * @param \Doctrine\Common\Persistence\ObjectManager $entityManager The EntityManager to use.
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata The class descriptor.
     */
    public function __construct(\Doctrine\Common\Persistence\ObjectManager $entityManager, \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata = null)
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
     * @api
     */
    public function add($object)
    {
        $this->entityManager->persist($object);
    }

    /**
     * Removes an object from this repository.
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
     *  - findBy<PropertyName>($value, $caseSensitive = TRUE, $cacheResult = FALSE)
     *  - findOneBy<PropertyName>($value, $caseSensitive = TRUE, $cacheResult = FALSE)
     *  - countBy<PropertyName>($value, $caseSensitive = TRUE)
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
