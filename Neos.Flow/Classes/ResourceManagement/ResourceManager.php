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

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\ObjectAccess;
use Neos\Flow\ResourceManagement\Storage\StorageInterface;
use Neos\Flow\ResourceManagement\Storage\WritableStorageInterface;
use Neos\Flow\ResourceManagement\Target\TargetInterface;
use Neos\Flow\Utility\Environment;
use Neos\Utility\Unicode\Functions as UnicodeFunctions;

/**
 * The ResourceManager
 *
 * @Flow\Scope("singleton")
 * @api
 */
class ResourceManager
{
    /**
     * Names of the default collections for static and persistent resources.
     */
    const DEFAULT_STATIC_COLLECTION_NAME = 'static';
    const DEFAULT_PERSISTENT_COLLECTION_NAME = 'persistent';

    const PUBLIC_RESSOURCE_REGEXP = '#^resource://(?<packageKey>[^/]+)/Public/(?<relativePathAndFilename>.*)#';

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

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
     * @var array
     */
    protected $settings;

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @var array<Storage\StorageInterface>
     */
    protected $storages;

    /**
     * @var array<Target\TargetInterface>
     */
    protected $targets;

    /**
     * @var array<CollectionInterface>
     */
    protected $collections;

    /**
     * @var boolean
     */
    protected $initialized = false;

    /**
     * Injects the settings of this package
     *
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Initializes the ResourceManager by parsing the related configuration and registering the resource
     * stream wrapper.
     *
     * @return void
     */
    protected function initialize()
    {
        if ($this->initialized === true) {
            return;
        }

        $this->initializeStorages();
        $this->initializeTargets();
        $this->initializeCollections();
        $this->initialized = true;
    }

    /**
     * Imports a resource (file) from the given location as a persistent resource.
     *
     * On a successful import this method returns a PersistentResource object representing the
     * newly imported persistent resource and automatically publishes it to the configured
     * publication target.
     *
     * @param string|resource $source A URI (can therefore also be a path and filename) or a PHP resource stream(!) pointing to the PersistentResource to import
     * @param string $collectionName Name of the collection this new resource should be added to. By default the standard collection for persistent resources is used.
     * @param string $forcedPersistenceObjectIdentifier INTERNAL: Force the object identifier for this resource to the given UUID
     * @return PersistentResource A resource object representing the imported resource
     * @throws Exception
     * @api
     */
    public function importResource($source, $collectionName = ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME, $forcedPersistenceObjectIdentifier = null)
    {
        $this->initialize();
        if (!isset($this->collections[$collectionName])) {
            throw new Exception(sprintf('Tried to import a file into the resource collection "%s" but no such collection exists. Please check your settings and the code which triggered the import.', $collectionName), 1375196643);
        }

        /* @var CollectionInterface $collection */
        $collection = $this->collections[$collectionName];

        try {
            $resource = $collection->importResource($source);
            if ($forcedPersistenceObjectIdentifier !== null) {
                ObjectAccess::setProperty($resource, 'Persistence_Object_Identifier', $forcedPersistenceObjectIdentifier, true);
            }
            if (!is_resource($source)) {
                $pathInfo = UnicodeFunctions::pathinfo($source);
                $resource->setFilename($pathInfo['basename']);
            }
        } catch (Exception $exception) {
            throw new Exception(sprintf('Importing a file into the resource collection "%s" failed: %s', $collectionName, $exception->getMessage()), 1375197120, $exception);
        }

        $this->resourceRepository->add($resource);
        $this->systemLogger->log(sprintf('Successfully imported file "%s" into the resource collection "%s" (storage: %s, a %s. SHA1: %s)', $source, $collectionName, $collection->getStorage()->getName(), get_class($collection), $resource->getSha1()), LOG_DEBUG);
        return $resource;
    }

    /**
     * Imports the given content passed as a string as a new persistent resource.
     *
     * The given content typically is binary data or a text format. On a successful import this method
     * returns a PersistentResource object representing the imported content and automatically publishes it to the
     * configured publication target.
     *
     * The specified filename will be used when presenting the resource to a user. Its file extension is
     * important because the resource management will derive the IANA Media Type from it.
     *
     * @param string $content The binary content to import
     * @param string $filename The filename to use for the newly generated resource
     * @param string $collectionName Name of the collection this new resource should be added to. By default the standard collection for persistent resources is used.
     * @param string $forcedPersistenceObjectIdentifier INTERNAL: Force the object identifier for this resource to the given UUID
     * @return PersistentResource A resource object representing the imported resource
     * @throws Exception
     * @api
     */
    public function importResourceFromContent($content, $filename, $collectionName = ResourceManager::DEFAULT_PERSISTENT_COLLECTION_NAME, $forcedPersistenceObjectIdentifier = null)
    {
        if (!is_string($content)) {
            throw new Exception(sprintf('Tried to import content into the resource collection "%s" but the given content was a %s instead of a string.', $collectionName, gettype($content)), 1380878115);
        }
        $this->initialize();

        if (!isset($this->collections[$collectionName])) {
            throw new Exception(sprintf('Tried to import a file into the resource collection "%s" but no such collection exists. Please check your settings and the code which triggered the import.', $collectionName), 1380878131);
        }

        /* @var CollectionInterface $collection */
        $collection = $this->collections[$collectionName];

        try {
            $resource = $collection->importResourceFromContent($content);
            $resource->setFilename($filename);
            if ($forcedPersistenceObjectIdentifier !== null) {
                ObjectAccess::setProperty($resource, 'Persistence_Object_Identifier', $forcedPersistenceObjectIdentifier, true);
            }
        } catch (Exception $exception) {
            throw new Exception(sprintf('Importing content into the resource collection "%s" failed: %s', $collectionName, $exception->getMessage()), 1381156155, $exception);
        }

        $this->resourceRepository->add($resource);
        $this->systemLogger->log(sprintf('Successfully imported content into the resource collection "%s" (storage: %s, a %s. SHA1: %s)', $collectionName, $collection->getStorage()->getName(), get_class($collection->getStorage()), $resource->getSha1()), LOG_DEBUG);

        return $resource;
    }

    /**
     * Imports a resource (file) from the given upload info array as a persistent
     * resource.
     *
     * On a successful import this method returns a PersistentResource object representing
     * the newly imported persistent resource.
     *
     * @param array $uploadInfo An array detailing the resource to import (expected keys: name, tmp_name)
     * @param string $collectionName Name of the collection this uploaded resource should be added to
     * @return PersistentResource A resource object representing the imported resource
     * @throws Exception
     */
    public function importUploadedResource(array $uploadInfo, $collectionName = self::DEFAULT_PERSISTENT_COLLECTION_NAME)
    {
        $this->initialize();
        if (!isset($this->collections[$collectionName])) {
            throw new Exception(sprintf('Tried to import an uploaded file into the resource collection "%s" but no such collection exists. Please check your settings and HTML forms.', $collectionName), 1375197544);
        }

        /* @var CollectionInterface $collection */
        $collection = $this->collections[$collectionName];

        try {
            $uploadedFile = $this->prepareUploadedFileForImport($uploadInfo);
            $resource = $collection->importResource($uploadedFile['filepath']);
            $resource->setFilename($uploadedFile['filename']);
        } catch (Exception $exception) {
            throw new Exception(sprintf('Importing an uploaded file into the resource collection "%s" failed.', $collectionName), 1375197680, $exception);
        }

        $this->resourceRepository->add($resource);
        $this->systemLogger->log(sprintf('Successfully imported the uploaded file "%s" into the resource collection "%s" (storage: "%s", a %s. SHA1: %s)', $resource->getFilename(), $collectionName, $this->collections[$collectionName]->getStorage()->getName(), get_class($this->collections[$collectionName]->getStorage()), $resource->getSha1()), LOG_DEBUG);

        return $resource;
    }

    /**
     * Returns the resource object identified by the given SHA1 hash over the content, or NULL if no such PersistentResource
     * object is known yet.
     *
     * @param string $sha1Hash The SHA1 identifying the data the PersistentResource stands for
     * @return PersistentResource|NULL
     * @api
     */
    public function getResourceBySha1($sha1Hash)
    {
        return $this->resourceRepository->findOneBySha1($sha1Hash);
    }

    /**
     * Returns a stream handle of the given persistent resource which allows for opening / copying the resource's
     * data. Note that this stream handle may only be used read-only.
     *
     * @param PersistentResource $resource The resource to retrieve the stream for
     * @return resource|boolean The resource stream or FALSE if the stream could not be obtained
     * @api
     */
    public function getStreamByResource(PersistentResource $resource)
    {
        $this->initialize();
        $collectionName = $resource->getCollectionName();
        if (!isset($this->collections[$collectionName])) {
            return false;
        }
        return $this->collections[$collectionName]->getStreamByResource($resource);
    }

    /**
     * Returns an object storage with all resource objects which have been imported
     * by the ResourceManager during this script call. Each resource comes with
     * an array of additional information about its import.
     *
     * Example for a returned object storage:
     *
     * $resource1 => array('originalFilename' => 'Foo.txt'),
     * $resource2 => array('originalFilename' => 'Bar.txt'),
     * ...
     *
     * @return \SplObjectStorage
     * @api
     */
    public function getImportedResources()
    {
        return $this->resourceRepository->getAddedResources();
    }

    /**
     * Deletes the given PersistentResource from the ResourceRepository and, if the storage data is no longer used in another
     * PersistentResource object, also deletes the data from the storage.
     *
     * This method will also remove the PersistentResource object from the (internal) ResourceRepository.
     *
     * @param PersistentResource $resource The resource to delete
     * @param boolean $unpublishResource If the resource should be unpublished before deleting it from the storage
     * @return boolean true if the resource was deleted, otherwise FALSE
     * @api
     */
    public function deleteResource(PersistentResource $resource, $unpublishResource = true)
    {
        $this->initialize();

        $collectionName = $resource->getCollectionName();

        $result = $this->resourceRepository->findBySha1($resource->getSha1());
        if (count($result) > 1) {
            $this->systemLogger->log(sprintf('Not removing storage data of resource %s (%s) because it is still in use by %s other PersistentResource object(s).', $resource->getFilename(), $resource->getSha1(), count($result) - 1), LOG_DEBUG);
        } else {
            if (!isset($this->collections[$collectionName])) {
                $this->systemLogger->log(sprintf('Could not remove storage data of resource %s (%s) because it refers to the unknown collection "%s".', $resource->getFilename(), $resource->getSha1(), $collectionName), LOG_WARNING);

                return false;
            }
            $storage = $this->collections[$collectionName]->getStorage();
            if (!$storage instanceof WritableStorageInterface) {
                $this->systemLogger->log(sprintf('Could not remove storage data of resource %s (%s) because it its collection "%s" is read-only.', $resource->getFilename(), $resource->getSha1(), $collectionName), LOG_WARNING);

                return false;
            }
            try {
                $storage->deleteResource($resource);
            } catch (\Exception $exception) {
                $this->systemLogger->log(sprintf('Could not remove storage data of resource %s (%s): %s.', $resource->getFilename(), $resource->getSha1(), $exception->getMessage()), LOG_WARNING);

                return false;
            }
            if ($unpublishResource) {
                /** @var TargetInterface $target */
                $target = $this->collections[$collectionName]->getTarget();
                $target->unpublishResource($resource);
                $this->systemLogger->log(sprintf('Removed storage data and unpublished resource %s (%s) because it not used by any other PersistentResource object.', $resource->getFilename(), $resource->getSha1()), LOG_DEBUG);
            } else {
                $this->systemLogger->log(sprintf('Removed storage data of resource %s (%s) because it not used by any other PersistentResource object.', $resource->getFilename(), $resource->getSha1()), LOG_DEBUG);
            }
        }

        $resource->setDeleted();
        $this->resourceRepository->remove($resource);

        return true;
    }

    /**
     * Returns the web accessible URI for the given resource object
     *
     * @param PersistentResource $resource The resource object
     * @return string|boolean A URI as a string or FALSE if the collection of the resource is not found
     * @api
     */
    public function getPublicPersistentResourceUri(PersistentResource $resource)
    {
        $this->initialize();

        if (!isset($this->collections[$resource->getCollectionName()])) {
            return false;
        }
        /** @var TargetInterface $target */
        $target = $this->collections[$resource->getCollectionName()]->getTarget();

        return $target->getPublicPersistentResourceUri($resource);
    }

    /**
     * Returns the web accessible URI for the resource object specified by the
     * given SHA1 hash.
     *
     * @param string $resourceHash The SHA1 hash identifying the resource content
     * @param string $collectionName Name of the collection the resource is part of
     * @return string A URI as a string
     * @throws Exception
     * @api
     */
    public function getPublicPersistentResourceUriByHash($resourceHash, $collectionName = self::DEFAULT_PERSISTENT_COLLECTION_NAME)
    {
        $this->initialize();

        if (!isset($this->collections[$collectionName])) {
            throw new Exception(sprintf('Could not determine persistent resource URI for "%s" because the specified collection "%s" does not exist.', $resourceHash, $collectionName), 1375197875);
        }
        /** @var TargetInterface $target */
        $target = $this->collections[$collectionName]->getTarget();
        $resource = $this->resourceRepository->findOneBySha1($resourceHash);
        if ($resource === null) {
            throw new Exception(sprintf('Could not determine persistent resource URI for "%s" because no PersistentResource object with that SHA1 hash could be found.', $resourceHash), 1375347691);
        }

        return $target->getPublicPersistentResourceUri($resource);
    }

    /**
     * Returns the public URI for a static resource provided by the specified package and in the given
     * path below the package's resources directory.
     *
     * @param string $packageKey Package key
     * @param string $relativePathAndFilename A relative path below the "Resources" directory of the package
     * @return string
     * @api
     */
    public function getPublicPackageResourceUri($packageKey, $relativePathAndFilename)
    {
        $this->initialize();

        /** @var TargetInterface $target */
        $target = $this->collections[self::DEFAULT_STATIC_COLLECTION_NAME]->getTarget();
        return $target->getPublicStaticResourceUri($packageKey . '/' . $relativePathAndFilename);
    }

    /**
     * Returns the public URI for a static resource provided by the public package
     *
     * @param string $path The ressource path, like resource://Your.Package/Public/Image/Dummy.png
     * @return string
     * @api
     */
    public function getPublicPackageResourceUriByPath($path)
    {
        $this->initialize();
        list($packageKey, $relativePathAndFilename) = $this->getPackageAndPathByPublicPath($path);
        return $this->getPublicPackageResourceUri($packageKey, $relativePathAndFilename);
    }

    /**
     * Return the package key and the relative path and filename from the given resource path
     *
     * @param string $path The ressource path, like resource://Your.Package/Public/Image/Dummy.png
     * @return array The array contains two value, first the packageKey followed by the relativePathAndFilename
     * @throws Exception
     * @api
     */
    public function getPackageAndPathByPublicPath($path)
    {
        if (preg_match(self::PUBLIC_RESSOURCE_REGEXP, $path, $matches) !== 1) {
            throw new Exception(sprintf('The path "%s" which was given must point to a public resource.', $path), 1450358448);
        }
        return [
            0 => $matches['packageKey'],
            1 => $matches['relativePathAndFilename']
        ];
    }

    /**
     * Returns a Storage instance by the given name
     *
     * @param string $storageName Name of the storage as defined in the settings
     * @return StorageInterface or NULL
     */
    public function getStorage($storageName)
    {
        $this->initialize();

        return isset($this->storages[$storageName]) ? $this->storages[$storageName] : null;
    }

    /**
     * Returns a Collection instance by the given name
     *
     * @param string $collectionName Name of the collection as defined in the settings
     * @return CollectionInterface or NULL
     * @api
     */
    public function getCollection($collectionName)
    {
        $this->initialize();

        return isset($this->collections[$collectionName]) ? $this->collections[$collectionName] : null;
    }

    /**
     * Returns an array of currently known Collection instances
     *
     * @return array<CollectionInterface>
     */
    public function getCollections()
    {
        $this->initialize();

        return $this->collections;
    }

    /**
     * Returns an array of Collection instances which use the given storage
     *
     * @param StorageInterface $storage
     * @return array<CollectionInterface>
     */
    public function getCollectionsByStorage(StorageInterface $storage)
    {
        $this->initialize();

        $collections = [];
        foreach ($this->collections as $collectionName => $collection) {
            /** @var CollectionInterface $collection */
            if ($collection->getStorage() === $storage) {
                $collections[$collectionName] = $collection;
            }
        }
        return $collections;
    }

    /**
     * Checks if recently imported resources really have been persisted - and if not, removes its data from the
     * respective storage.
     *
     * @return void
     */
    public function shutdownObject()
    {
        /** @var PersistentResource $resource */
        foreach ($this->resourceRepository->getAddedResources() as $resource) {
            if ($this->persistenceManager->isNewObject($resource)) {
                $this->deleteResource($resource, false);
            }
        }
    }

    /**
     * Initializes the Storage objects according to the current settings
     *
     * @return void
     * @throws Exception if the storage configuration is invalid
     */
    protected function initializeStorages()
    {
        foreach ($this->settings['resource']['storages'] as $storageName => $storageDefinition) {
            if (!isset($storageDefinition['storage'])) {
                throw new Exception(sprintf('The configuration for the resource storage "%s" defined in your settings has no valid "storage" option. Please check the configuration syntax and make sure to specify a valid storage class name.', $storageName), 1361467211);
            }
            if (!class_exists($storageDefinition['storage'])) {
                throw new Exception(sprintf('The configuration for the resource storage "%s" defined in your settings has not defined a valid "storage" option. Please check the configuration syntax and make sure that the specified class "%s" really exists.', $storageName, $storageDefinition['storage']), 1361467212);
            }
            $options = (isset($storageDefinition['storageOptions']) ? $storageDefinition['storageOptions'] : []);
            $this->storages[$storageName] = new $storageDefinition['storage']($storageName, $options);
        }
    }

    /**
     * Initializes the Target objects according to the current settings
     *
     * @return void
     * @throws Exception if the target configuration is invalid
     */
    protected function initializeTargets()
    {
        foreach ($this->settings['resource']['targets'] as $targetName => $targetDefinition) {
            if (!isset($targetDefinition['target'])) {
                throw new Exception(sprintf('The configuration for the resource target "%s" defined in your settings has no valid "target" option. Please check the configuration syntax and make sure to specify a valid target class name.', $targetName), 1361467838);
            }
            if (!class_exists($targetDefinition['target'])) {
                throw new Exception(sprintf('The configuration for the resource target "%s" defined in your settings has not defined a valid "target" option. Please check the configuration syntax and make sure that the specified class "%s" really exists.', $targetName, $targetDefinition['target']), 1361467839);
            }
            $options = (isset($targetDefinition['targetOptions']) ? $targetDefinition['targetOptions'] : []);
            $this->targets[$targetName] = new $targetDefinition['target']($targetName, $options);
        }
    }

    /**
     * Initializes the Collection objects according to the current settings
     *
     * @return void
     * @throws Exception if the collection configuration is invalid
     */
    protected function initializeCollections()
    {
        foreach ($this->settings['resource']['collections'] as $collectionName => $collectionDefinition) {
            if (!isset($collectionDefinition['storage'])) {
                throw new Exception(sprintf('The configuration for the resource collection "%s" defined in your settings has no valid "storage" option. Please check the configuration syntax.', $collectionName), 1361468805);
            }
            if (!isset($this->storages[$collectionDefinition['storage']])) {
                throw new Exception(sprintf('The configuration for the resource collection "%s" defined in your settings referred to a non-existing storage "%s". Please check the configuration syntax and make sure to specify a valid storage class name.', $collectionName, $collectionDefinition['storage']), 1361481031);
            }
            if (!isset($collectionDefinition['target'])) {
                throw new Exception(sprintf('The configuration for the resource collection "%s" defined in your settings has no valid "target" option. Please check the configuration syntax and make sure to specify a valid target class name.', $collectionName), 1361468923);
            }
            if (!isset($this->targets[$collectionDefinition['target']])) {
                throw new Exception(sprintf('The configuration for the resource collection "%s" defined in your settings has not defined a valid "target" option. Please check the configuration syntax and make sure that the specified class "%s" really exists.', $collectionName, $collectionDefinition['target']), 1361468924);
            }

            $pathPatterns = (isset($collectionDefinition['pathPatterns'])) ? $collectionDefinition['pathPatterns'] : [];
            $filenames = (isset($collectionDefinition['filenames'])) ? $collectionDefinition['filenames'] : [];

            $this->collections[$collectionName] = new Collection($collectionName, $this->storages[$collectionDefinition['storage']], $this->targets[$collectionDefinition['target']], $pathPatterns, $filenames);
        }
    }

    /**
     * Prepare an uploaded file to be imported as resource object. Will check the validity of the file,
     * move it outside of upload folder if open_basedir is enabled and check the filename.
     *
     * @param array $uploadInfo
     * @return array Array of string with the two keys "filepath" (the path to get the filecontent from) and "filename" the filename of the originally uploaded file.
     * @throws Exception
     */
    protected function prepareUploadedFileForImport(array $uploadInfo)
    {
        $openBasedirEnabled = (boolean)ini_get('open_basedir');
        $temporaryTargetPathAndFilename = $uploadInfo['tmp_name'];
        $pathInfo = UnicodeFunctions::pathinfo($uploadInfo['name']);

        if (!is_uploaded_file($temporaryTargetPathAndFilename)) {
            throw new Exception('The given upload file "' . strip_tags($pathInfo['basename']) . '" was not uploaded through PHP. As it could pose a security risk it cannot be imported.', 1422461503);
        }

        if (isset($pathInfo['extension']) && array_key_exists(strtolower($pathInfo['extension']), $this->settings['resource']['uploadExtensionBlacklist']) && $this->settings['resource']['uploadExtensionBlacklist'][strtolower($pathInfo['extension'])] === true) {
            throw new Exception('The extension of the given upload file "' . strip_tags($pathInfo['basename']) . '" is blacklisted. As it could pose a security risk it cannot be imported.', 1447148472);
        }

        if ($openBasedirEnabled === true) {
            // Move uploaded file to a readable folder before trying to read sha1 value of file
            $newTemporaryTargetPathAndFilename = $this->environment->getPathToTemporaryDirectory() . 'ResourceUpload.' . uniqid() . '.tmp';
            if (move_uploaded_file($temporaryTargetPathAndFilename, $newTemporaryTargetPathAndFilename) === false) {
                throw new Exception(sprintf('The uploaded file "%s" could not be moved to the temporary location "%s".', $temporaryTargetPathAndFilename, $newTemporaryTargetPathAndFilename), 1375199056);
            }
            $temporaryTargetPathAndFilename = $newTemporaryTargetPathAndFilename;
        }

        if (!is_file($temporaryTargetPathAndFilename)) {
            throw new Exception(sprintf('The temporary file "%s" of the file upload does not exist (anymore).', $temporaryTargetPathAndFilename), 1375198998);
        }

        return [
            'filepath' => $temporaryTargetPathAndFilename,
            'filename' => $pathInfo['basename']
        ];
    }
}
