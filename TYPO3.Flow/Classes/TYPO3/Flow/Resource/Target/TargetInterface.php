<?php
namespace TYPO3\Flow\Resource\Target;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Interface for a resource publishing target
 */
use TYPO3\Flow\Resource\CollectionInterface;
use TYPO3\Flow\Resource\Resource;

interface TargetInterface
{
    /**
     * Returns the name of this target instance
     *
     * @return string
     */
    public function getName();

    /**
     * Publishes the whole collection to this target
     *
     * @param CollectionInterface $collection The collection to publish
     * @return void
     */
    public function publishCollection(CollectionInterface $collection);

    /**
     * Publishes the given persistent resource from the given storage
     *
     * @param Resource $resource The resource to publish
     * @param CollectionInterface $collection The collection the given resource belongs to
     * @return void
     * @throws Exception
     */
    public function publishResource(Resource $resource, CollectionInterface $collection);

    /**
     * Unpublishes the given persistent resource
     *
     * @param Resource $resource The resource to unpublish
     * @return void
     */
    public function unpublishResource(Resource $resource);

    /**
     * Returns the web accessible URI pointing to the given static resource
     *
     * @param string $relativePathAndFilename Relative path and filename of the static resource
     * @return string The URI
     */
    public function getPublicStaticResourceUri($relativePathAndFilename);

    /**
     * Returns the web accessible URI pointing to the specified persistent resource
     *
     * @param Resource $resource Resource object
     * @return string The URI
     * @throws Exception
     */
    public function getPublicPersistentResourceUri(Resource $resource);
}
