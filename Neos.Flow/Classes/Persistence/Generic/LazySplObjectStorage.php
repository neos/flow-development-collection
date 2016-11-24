<?php
namespace Neos\Flow\Persistence\Generic;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Persistence\PersistenceManagerInterface;

/**
 * A lazy loading variant of \SplObjectStorage
 *
 * @api
 */
class LazySplObjectStorage extends \SplObjectStorage
{
    /**
     * The identifiers of the objects contained in the \SplObjectStorage
     * @var array
     */
    protected $objectIdentifiers = [];

    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @param PersistenceManagerInterface $persistenceManager
     * @return void
     */
    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * @param array $objectIdentifiers
     */
    public function __construct(array $objectIdentifiers)
    {
        $this->objectIdentifiers = $objectIdentifiers;
    }

    /**
     * Loads the objects this LazySplObjectStorage is supposed to hold.
     *
     * @return void
     */
    protected function initialize()
    {
        if (is_array($this->objectIdentifiers)) {
            foreach ($this->objectIdentifiers as $identifier) {
                try {
                    parent::attach($this->persistenceManager->getObjectByIdentifier($identifier));
                } catch (Exception\InvalidObjectDataException $exception) {
                    // when security query rewriting holds back an object here, we skip it...
                }
            }
            $this->objectIdentifiers = null;
        }
    }

    /**
     * Returns TRUE if the LazySplObjectStorage has been initialized.
     *
     * @return boolean
     */
    public function isInitialized()
    {
        return !is_array($this->objectIdentifiers);
    }


    // Standard SplObjectStorage methods below


    public function addAll($storage)
    {
        $this->initialize();
        parent::addAll($storage);
    }

    public function attach($object, $data = null)
    {
        $this->initialize();
        parent::attach($object, $data);
    }

    public function contains($object)
    {
        $this->initialize();
        return parent::contains($object);
    }

    public function count()
    {
        if (is_array($this->objectIdentifiers)) {
            return count($this->objectIdentifiers);
        } else {
            return parent::count();
        }
    }

    public function current()
    {
        $this->initialize();
        return parent::current();
    }

    public function detach($object)
    {
        $this->initialize();
        parent::detach($object);
    }

    public function getInfo()
    {
        $this->initialize();
        return parent::getInfo();
    }

    public function key()
    {
        $this->initialize();
        return parent::key();
    }

    public function next()
    {
        $this->initialize();
        parent::next();
    }

    public function offsetExists($object)
    {
        $this->initialize();
        return parent::offsetExists($object);
    }

    public function offsetGet($object)
    {
        $this->initialize();
        return parent::offsetGet($object);
    }

    public function offsetSet($object, $info)
    {
        $this->initialize();
        parent::offsetSet($object, $info);
    }

    public function offsetUnset($object)
    {
        $this->initialize();
        parent::offsetUnset($object);
    }

    public function removeAll($storage)
    {
        $this->initialize();
        parent::removeAll($storage);
    }

    public function rewind()
    {
        $this->initialize();
        parent::rewind();
    }

    public function setInfo($data)
    {
        $this->initialize();
        parent::setInfo($data);
    }
    public function valid()
    {
        $this->initialize();
        return parent::valid();
    }


    // we don't do those (yet)


    public function serialize()
    {
        throw new \RuntimeException('A LazyLoadingSplObjectStorage instance cannot be serialized.', 1267700868);
    }

    public function unserialize($serialized)
    {
        throw new \RuntimeException('A LazyLoadingSplObjectStorage instance cannot be unserialized.', 1267700870);
    }
}
