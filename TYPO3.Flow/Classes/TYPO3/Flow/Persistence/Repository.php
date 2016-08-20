<?php
namespace TYPO3\Flow\Persistence;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * The Flow default Repository
 *
 * @api
 */
abstract class Repository implements \TYPO3\Flow\Persistence\RepositoryInterface
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Warning: if you think you want to set this,
     * look at RepositoryInterface::ENTITY_CLASSNAME first!
     *
     * @var string
     */
    protected $entityClassName;

    /**
     * @var array
     */
    protected $defaultOrderings = array();

    /**
     * Initializes a new Repository.
     */
    public function __construct()
    {
        if (defined('static::ENTITY_CLASSNAME') === false) {
            $this->entityClassName = preg_replace(array('/\\\Repository\\\/', '/Repository$/'), array('\\Model\\', ''), get_class($this));
        } else {
            $this->entityClassName = static::ENTITY_CLASSNAME;
        }
    }

    /**
     * Returns the classname of the entities this repository is managing.
     *
     * Note that anything that is an "instanceof" this class is accepted
     * by the repository.
     *
     * @return string
     * @api
     */
    public function getEntityClassName()
    {
        return $this->entityClassName;
    }

    /**
     * Adds an object to this repository.
     *
     * @param object $object The object to add
     * @return void
     * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
     * @api
     */
    public function add($object)
    {
        if (!is_object($object) || !($object instanceof $this->entityClassName)) {
            $type = (is_object($object) ? get_class($object) : gettype($object));
            throw new \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException('The value given to add() was ' . $type . ' , however the ' . get_class($this) . ' can only store ' . $this->entityClassName . ' instances.', 1298403438);
        }
        $this->persistenceManager->add($object);
    }

    /**
     * Removes an object from this repository.
     *
     * @param object $object The object to remove
     * @return void
     * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
     * @api
     */
    public function remove($object)
    {
        if (!is_object($object) || !($object instanceof $this->entityClassName)) {
            $type = (is_object($object) ? get_class($object) : gettype($object));
            throw new \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException('The value given to remove() was ' . $type . ' , however the ' . get_class($this) . ' can only handle ' . $this->entityClassName . ' instances.', 1298403442);
        }
        $this->persistenceManager->remove($object);
    }

    /**
     * Returns all objects of this repository
     *
     * @return \TYPO3\Flow\Persistence\QueryResultInterface The query result
     * @api
     * @see \TYPO3\Flow\Persistence\QueryInterface::execute()
     */
    public function findAll()
    {
        return $this->createQuery()->execute();
    }

    /**
     * Finds an object matching the given identifier.
     *
     * @param string $identifier The identifier of the object to find
     * @return object The matching object if found, otherwise NULL
     * @api
     */
    public function findByIdentifier($identifier)
    {
        return $this->persistenceManager->getObjectByIdentifier($identifier, $this->entityClassName);
    }

    /**
     * Returns a query for objects of this repository
     *
     * @return \TYPO3\Flow\Persistence\QueryInterface
     * @api
     */
    public function createQuery()
    {
        $query = $this->persistenceManager->createQueryForType($this->entityClassName);
        if ($this->defaultOrderings !== array()) {
            $query->setOrderings($this->defaultOrderings);
        }
        return $query;
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
     * @todo use DQL here, would be much more performant
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
     *  'foo' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
     *  'bar' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING
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
     * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
     * @api
     */
    public function update($object)
    {
        if (!is_object($object) || !($object instanceof $this->entityClassName)) {
            $type = (is_object($object) ? get_class($object) : gettype($object));
            throw new \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException('The value given to update() was ' . $type . ' , however the ' . get_class($this) . ' can only store ' . $this->entityClassName . ' instances.', 1249479625);
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
