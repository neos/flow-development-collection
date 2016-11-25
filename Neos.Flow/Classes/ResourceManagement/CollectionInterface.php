<?php
namespace Neos\Flow\ResourceManagement;

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
use Neos\Flow\ResourceManagement\Storage\StorageInterface;
use Neos\Flow\ResourceManagement\Target\TargetInterface;
use Neos\Flow\ResourceManagement\Exception as ResourceException;

/**
 * Interface for a resource collection
 */
interface CollectionInterface
{
    /**
     * Returns the name of this collection
     *
     * @return string
     */
    public function getName();

    /**
     * Returns the storage used for this collection
     *
     * @return StorageInterface
     */
    public function getStorage();

    /**
     * Returns the publication target defined for this collection
     *
     * @return TargetInterface
     */
    public function getTarget();

    /**
     * Imports a resource (file) from the given URI or PHP resource stream into this collection.
     *
     * On a successful import this method returns a PersistentResource object representing the newly
     * imported persistent resource.
     *
     * Note that this collection must have a writable storage in order to import resources.
     *
     * @param string | resource $source The URI (or local path and filename) or the PHP resource stream to import the resource from
     * @return PersistentResource A resource object representing the imported resource
     * @throws ResourceException
     */
    public function importResource($source);

    /**
     * Imports a resource from the given string content into this collection.
     *
     * On a successful import this method returns a PersistentResource object representing the newly
     * imported persistent resource.
     *
     * Note that this collection must have a writable storage in order to import resources.
     *
     * @param string $content The actual content to import
     * @return PersistentResource A resource object representing the imported resource
     * @throws ResourceException
     */
    public function importResourceFromContent($content);

    /**
     * Publishes the whole collection to the corresponding publishing target
     *
     * @return void
     */
    public function publish();

    /**
     * Returns all internal data objects of the storage attached to this collection.
     *
     * @return \Generator<Storage\Object>
     */
    public function getObjects();

    /**
     * Returns a stream handle of the given persistent resource which allows for opening / copying the resource's
     * data. Note that this stream handle may only be used read-only.
     *
     * @param PersistentResource $resource The resource to retrieve the stream for
     * @return resource | boolean The resource stream or FALSE if the stream could not be obtained
     */
    public function getStreamByResource(PersistentResource $resource);
}
