<?php
namespace TYPO3\Flow\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Resource\CollectionInterface;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Flow\Resource\ResourceRepository;

/**
 * Resource command controller for the TYPO3.Flow package
 *
 * @Flow\Scope("singleton")
 */
class ResourceCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var ResourceRepository
     */
    protected $resourceRepository;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Publish resources
     *
     * This command publishes the resources of the given or - if none was specified, all - resource collections
     * to their respective configured publishing targets.
     *
     * @param string $collection If specified, only resources of this collection are published. Example: 'persistent'
     * @return void
     */
    public function publishCommand($collection = null)
    {
        try {
            if ($collection === null) {
                $collections = $this->resourceManager->getCollections();
            } else {
                $collections = array();
                $collections[$collection] = $this->resourceManager->getCollection($collection);
                if ($collections[$collection] === null) {
                    $this->outputLine('Collection "%s" does not exist.', array($collection));
                    $this->quit(1);
                }
            }

            foreach ($collections as $collection) {
                /** @var CollectionInterface $collection */
                $this->outputLine('Publishing resources of collection "%s"', array($collection->getName()));
                $collection->publish();
            }
        } catch (Exception $exception) {
            $this->outputLine();
            $this->outputLine('An error occurred while publishing resources (see full description below). You can check and probably fix the integrity of the resource registry by using the resource:clean command.');
            $this->outputLine('%s (Exception code: %s)', array(get_class($exception), $exception->getCode()));
            $this->outputLine($exception->getMessage());
            $this->quit(1);
        }
    }

    /**
     * Copy all assets of a collection from one storage to another
     *
     * This command copies all resources from one collection to another storage identified by name.
     *
     * The target storage must be empty and should not be identical to the current storage of the collection.
     * Otherwise this could cause conflicts.
     *
     * It does not clone the references to the resources. It's just a dumb copy so you could change the
     * storage of the collection afterwards.
     *
     * @param string $sourceCollection The name of the collection you want to copy the assets from
     * @param string $targetCollection The name of the collection you want to copy the assets to
     * @return void
     */
    public function copyCommand($sourceCollection, $targetCollection)
    {
        $srcCollection = $this->resourceManager->getCollection($sourceCollection);
        if ($srcCollection === null) {
            $this->outputLine('Source collection "%s" does not exist.', array($sourceCollection));
            $this->quit(1);
        }

        $dstCollection = $this->resourceManager->getCollection($targetCollection);
        if ($dstCollection === null) {
            $this->outputLine('Target collection "%s" does not exist.', array($targetCollection));
            $this->quit(1);
        }

        if (!empty($dstCollection->getObjects())) {
            $this->outputLine('Target collection "%s" is not empty.', array($targetCollection));
            $this->quit(1);
        }

        foreach ($srcCollection->getObjects() as $resource) {
            /** @var \TYPO3\Flow\Resource\Storage\Object $resource */
            $dstCollection->importResource($resource->getStream());
        }
        $this->outputLine('Copied %s resource objects from collection "%s" to collection "%s"', [count($srcCollection->getObjects()), $sourceCollection, $targetCollection]);
        $dstCollection->getTarget()->publishCollection($srcCollection);
        $this->outputLine('Published copied resources to target "%s"', [$dstCollection->getTarget()->getName()]);
        $this->outputLine('Hint: If you want to use the target collection as a replacement for your current one you can now modify your configuration.');
    }

    /**
     * Clean up resource registry
     *
     * This command checks the resource registry (that is the database tables) for orphaned resource objects which don't
     * seem to have any corresponding data anymore (for example: the file in Data/Persistent/Resources has been deleted
     * without removing the related Resource object).
     *
     * If the TYPO3.Media package is active, this command will also detect any assets referring to broken resources
     * and will remove the respective Asset object from the database when the broken resource is removed.
     *
     * This command will ask you interactively what to do before deleting anything.
     *
     * @return void
     */
    public function cleanCommand()
    {
        $this->outputLine('Checking if resource data exists for all known resource objects ...');
        $this->outputLine();

        $mediaPackagePresent = $this->packageManager->isPackageActive('TYPO3.Media');

        $resourcesCount = $this->resourceRepository->countAll();
        $this->output->progressStart($resourcesCount);

        $brokenResources = array();
        $relatedAssets = new \SplObjectStorage();
        foreach ($this->resourceRepository->findAll() as $resource) {
            $this->output->progressAdvance(1);
            /* @var \TYPO3\Flow\Resource\Resource $resource */
            $stream = $resource->getStream();
            if (!is_resource($stream)) {
                $brokenResources[] = $resource;
            }
        }

        $this->output->progressFinish();
        $this->outputLine();

        if ($mediaPackagePresent && count($brokenResources) > 0) {
            $assetRepository = $this->objectManager->get(\TYPO3\Media\Domain\Repository\AssetRepository::class);
            /* @var \TYPO3\Media\Domain\Repository\AssetRepository $assetRepository */

            foreach ($brokenResources as $resource) {
                $assets = $assetRepository->findByResource($resource);
                if ($assets !== null) {
                    $relatedAssets[$resource] = $assets;
                }
            }
        }

        if (count($brokenResources) > 0) {
            $this->outputLine('<b>Found %s broken resource(s):</b>', array(count($brokenResources)));
            $this->outputLine();

            foreach ($brokenResources as $resource) {
                $this->outputLine('%s (%s) %s', array($resource->getFilename(), $resource->getSha1(), $resource->getCollectionName()));
                if (isset($relatedAssets[$resource])) {
                    foreach ($relatedAssets[$resource] as $asset) {
                        $this->outputLine(' -> %s (%s)', array(get_class($asset), $asset->getIdentifier()));
                    }
                }
            }
            $response = null;
            while (!in_array($response, array('y', 'n', 'c'))) {
                $response = $this->output->ask('<comment>Do you want to remove all broken resource objects and related assets from the database? (y/n/c) </comment>');
            }

            switch ($response) {
                case 'y':
                    foreach ($brokenResources as $resource) {
                        $resource->disableLifecycleEvents();
                        $this->persistenceManager->remove($resource);
                        if (isset($relatedAssets[$resource])) {
                            foreach ($relatedAssets[$resource] as $asset) {
                                $assetRepository->remove($asset);
                            }
                        }
                    }
                    $this->outputLine('Removed %s resource object(s) from the database.', array(count($brokenResources)));
                break;
                case 'n':
                    $this->outputLine('Did not delete any resource objects.');
                break;
                case 'c':
                    $this->outputLine('Stopping. Did not delete any resource objects.');
                    $this->quit(0);
                break;
            }
        }
    }
}
