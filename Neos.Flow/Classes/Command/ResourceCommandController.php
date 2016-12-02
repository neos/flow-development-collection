<?php
namespace Neos\Flow\Command;

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
use Neos\Flow\Cli\CommandController;
use Neos\Error\Messages\Message;
use Neos\Flow\Exception;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\CollectionInterface;
use Neos\Flow\ResourceManagement\Publishing\MessageCollector;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Media\Domain\Repository\AssetRepository;

use Neos\Media\Domain\Repository\ThumbnailRepository;

/**
 * PersistentResource command controller for the Neos.Flow package
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
     * @Flow\Inject
     * @var MessageCollector
     */
    protected $messageCollector;

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
                $collections = [];
                $collections[$collection] = $this->resourceManager->getCollection($collection);
                if ($collections[$collection] === null) {
                    $this->outputLine('Collection "%s" does not exist.', [$collection]);
                    $this->quit(1);
                }
            }

            foreach ($collections as $collection) {
                /** @var CollectionInterface $collection */
                $this->outputLine('Publishing resources of collection "%s"', [$collection->getName()]);
                $target = $collection->getTarget();
                $target->publishCollection($collection, function ($iteration) {
                    $this->clearState($iteration);
                });
            }

            if ($this->messageCollector->hasMessages()) {
                $this->outputLine();
                $this->outputLine('The resources were published, but a few inconsistencies were detected. You can check and probably fix the integrity of the resource registry by using the resource:clean command.');
                $this->messageCollector->flush(function (Message $notification) {
                    $this->outputLine($notification->getSeverity() . ': ' . $notification->getMessage());
                });
            }
        } catch (Exception $exception) {
            $this->outputLine();
            $this->outputLine('An error occurred while publishing resources (see full description below). You can check and probably fix the integrity of the resource registry by using the resource:clean command.');
            $this->outputLine('%s (Exception code: %s)', [get_class($exception), $exception->getCode()]);
            $this->outputLine($exception->getMessage());
            $this->quit(1);
        }
    }

    /**
     * Copy resources
     *
     * This command copies all resources from one collection to another storage identified by name.
     * The target storage must be empty and must not be identical to the current storage of the collection.
     *
     * This command merely copies the binary data from one storage to another, it does not change the related
     * PersistentResource objects in the database in any way. Since the PersistentResource objects in the database refer to a
     * collection name, you can use this command for migrating from one storage to another my configuring
     * the new storage with the name of the old storage collection after the resources have been copied.
     *
     * @param string $sourceCollection The name of the collection you want to copy the assets from
     * @param string $targetCollection The name of the collection you want to copy the assets to
     * @param boolean $publish If enabled, the target collection will be published after the resources have been copied
     * @return void
     */
    public function copyCommand($sourceCollection, $targetCollection, $publish = false)
    {
        $sourceCollectionName = $sourceCollection;
        $sourceCollection = $this->resourceManager->getCollection($sourceCollectionName);
        if ($sourceCollection === null) {
            $this->outputLine('The source collection "%s" does not exist.', array($sourceCollectionName));
            $this->quit(1);
        }

        $targetCollectionName = $targetCollection;
        $targetCollection = $this->resourceManager->getCollection($targetCollection);
        if ($targetCollection === null) {
            $this->outputLine('The target collection "%s" does not exist.', array($targetCollectionName));
            $this->quit(1);
        }

        if (!empty($targetCollection->getObjects())) {
            $this->outputLine('The target collection "%s" is not empty.', array($targetCollectionName));
            $this->quit(1);
        }

        $sourceObjects = $sourceCollection->getObjects();
        $this->outputLine('Copying resource objects from collection "%s" to collection "%s" ...', [$sourceCollectionName, $targetCollectionName]);
        $this->outputLine();

        $this->output->progressStart(count($sourceObjects));
        foreach ($sourceCollection->getObjects() as $resource) {
            /** @var \Neos\Flow\ResourceManagement\Storage\StorageObject $resource */
            $this->output->progressAdvance();
            $targetCollection->importResource($resource->getStream());
        }
        $this->output->progressFinish();
        $this->outputLine();

        if ($publish) {
            $this->outputLine('Publishing copied resources to the target "%s" ...', [$targetCollection->getTarget()->getName()]);
            $targetCollection->getTarget()->publishCollection($sourceCollection);
        }

        $this->outputLine('Done.');
        $this->outputLine('Hint: If you want to use the target collection as a replacement for your current one, you can now modify your settings accordingly.');
    }

    /**
     * Clean up resource registry
     *
     * This command checks the resource registry (that is the database tables) for orphaned resource objects which don't
     * seem to have any corresponding data anymore (for example: the file in Data/Persistent/Resources has been deleted
     * without removing the related PersistentResource object).
     *
     * If the Neos.Media package is active, this command will also detect any assets referring to broken resources
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

        $mediaPackagePresent = $this->packageManager->isPackageActive('Neos.Media');

        $resourcesCount = $this->resourceRepository->countAll();
        $this->output->progressStart($resourcesCount);

        $brokenResources = [];
        $relatedAssets = new \SplObjectStorage();
        $relatedThumbnails = new \SplObjectStorage();
        $iterator = $this->resourceRepository->findAllIterator();
        foreach ($this->resourceRepository->iterate($iterator, function ($iteration) {
            $this->clearState($iteration);
        }) as $resource) {
            $this->output->progressAdvance(1);
            /* @var PersistentResource $resource */
            $stream = $resource->getStream();
            if (!is_resource($stream)) {
                $brokenResources[] = $resource->getSha1();
            }
        }

        $this->output->progressFinish();
        $this->outputLine();

        if ($mediaPackagePresent && count($brokenResources) > 0) {
            /* @var AssetRepository $assetRepository */
            $assetRepository = $this->objectManager->get(AssetRepository::class);
            /* @var ThumbnailRepository $thumbnailRepository */
            $thumbnailRepository = $this->objectManager->get(ThumbnailRepository::class);

            foreach ($brokenResources as $key => $resourceSha1) {
                $resource = $this->resourceRepository->findOneBySha1($resourceSha1);
                $brokenResources[$key] = $resource;
                $assets = $assetRepository->findByResource($resource);
                if ($assets !== null) {
                    $relatedAssets[$resource] = $assets;
                }
                $thumbnails = $thumbnailRepository->findByResource($resource);
                if ($assets !== null) {
                    $relatedThumbnails[$resource] = $thumbnails;
                }
            }
        }

        if (count($brokenResources) > 0) {
            $this->outputLine('<b>Found %s broken resource(s):</b>', [count($brokenResources)]);
            $this->outputLine();

            foreach ($brokenResources as $resource) {
                $this->outputLine('%s (%s) from "%s" collection', [$resource->getFilename(), $resource->getSha1(), $resource->getCollectionName()]);
                if (isset($relatedAssets[$resource])) {
                    foreach ($relatedAssets[$resource] as $asset) {
                        $this->outputLine(' -> %s (%s)', [get_class($asset), $asset->getIdentifier()]);
                    }
                }
            }
            $response = null;
            while (!in_array($response, ['y', 'n', 'c'])) {
                $response = $this->output->ask('<comment>Do you want to remove all broken resource objects and related assets from the database? (y/n/c) </comment>');
            }

            switch ($response) {
                case 'y':
                    $brokenAssetCounter = 0;
                    $brokenThumbnailCounter = 0;
                    foreach ($brokenResources as $sha1 => $resource) {
                        $this->outputLine('- delete %s (%s) from "%s" collection', [
                            $resource->getFilename(),
                            $resource->getSha1(),
                            $resource->getCollectionName()
                        ]);
                        $resource->disableLifecycleEvents();
                        $this->resourceRepository->remove($resource);
                        if (isset($relatedAssets[$resource])) {
                            foreach ($relatedAssets[$resource] as $asset) {
                                $assetRepository->remove($asset);
                                $brokenAssetCounter++;
                            }
                        }
                        if (isset($relatedThumbnails[$resource])) {
                            foreach ($relatedThumbnails[$resource] as $thumbnail) {
                                $thumbnailRepository->remove($thumbnail);
                                $brokenThumbnailCounter++;
                            }
                        }
                        $this->persistenceManager->persistAll();
                    }
                    $brokenResourcesCounter = count($brokenResources);
                    if ($brokenResourcesCounter > 0) {
                        $this->outputLine('Removed %s resource object(s) from the database.', [$brokenResourcesCounter]);
                    }
                    if ($brokenAssetCounter > 0) {
                        $this->outputLine('Removed %s asset object(s) from the database.', [$brokenAssetCounter]);
                    }
                    if ($brokenThumbnailCounter > 0) {
                        $this->outputLine('Removed %s thumbnail object(s) from the database.', [$brokenThumbnailCounter]);
                    }
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

    /**
     * This method is used internal as a callback method to clear doctrine states
     *
     * @param integer $iteration
     * @return void
     */
    protected function clearState($iteration)
    {
        if ($iteration % 1000 === 0) {
            $this->persistenceManager->clearState();
        }
    }
}
