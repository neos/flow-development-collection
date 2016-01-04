<?php
namespace TYPO3\Flow\Command;

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
     * Copy resources
     *
     * This command copies all resources from one collection to another storage identified by name.
     * The target storage must be empty and must not be identical to the current storage of the collection.
     *
     * This command merely copies the binary data from one storage to another, it does not change the related
     * Resource objects in the database in any way. Since the Resource objects in the database refer to a
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
            /** @var \TYPO3\Flow\Resource\Storage\Object $resource */
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
