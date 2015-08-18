<?php
namespace TYPO3\Flow\Resource\Publishing;

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
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Resource\ResourceManager;

/**
 * Resource Publisher (deprecated)
 *
 * NOTE: Although this class never belonged to the public API, the method
 *       getPersistentResourceWebUri() has been used in various packages.
 *       In order to keep backwards compatibility, we decided to leave this class
 *       containing the two methods in 3.0.x versions of Flow and mark them as deprecated.
 *
 *       Please make sure to use the new ResourceManager API instead!
 *
 * @Flow\Scope("singleton")
 * @deprecated
 */
class ResourcePublisher {

	/**
	 * @Flow\Inject
	 * @var ResourceManager
	 */
	protected $resourceManager;

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * Returns the URI pointing to the published persistent resource
	 *
	 * @param \TYPO3\Flow\Resource\Resource $resource The resource to publish
	 * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or the resource could not be published for other reasons
	 * @deprecated since Flow 3.0. Use ResourceManager->getPublicPersistentResourceUri($resource) instead
	 */
	public function getPersistentResourceWebUri(Resource $resource) {
		$this->systemLogger->log('The deprecated method ResourcePublisher->getPersistentResourceWebUri() has been called' . $this->getCallee() . '. Please use ResourceManager->getPublicPersistentResourceUri() instead!', LOG_WARNING);
		return $this->resourceManager->getPublicPersistentResourceUri($resource);
	}

	/**
	 * Returns the base URI for static resources
	 *
	 * IMPORTANT: This method merely exists in order to simplify migration from earlier versions of Flow which still
	 * provided this method. This method has never been part of the public API and will be removed in the future.
	 *
	 * Note that, depending on your Resource Collection setup, this method will not always return the correct base URI,
	 * because as of now there can be multiple publishing targets for static resources and URIs of the respective
	 * target might not work by simply concatenating a base URI with the relative file name.
	 *
	 * This method will work for the default Flow setup using only the local file system.
	 *
	 * Make sure to refactor your client code to use the new resource management API instead. There is no direct
	 * replacement for this method in the new API, but if you are dealing with static resources, use the resource stream
	 * wrapper instead (through URLs like "resource://TYPO3.Flow/Public/Error/Debugger.css") or use
	 * ResourceManager->getPublicPackageResourceUri() if you know the package key and relative path.
	 *
	 * Don't use this method. Ne pas utiliser cette méthode. No utilice este método. Finger weg!
	 * U bent gewaarschuwd! You have been warned! Mēs jūs brīdinām! Mir hams euch fei gsagd! ;-)
	 *
	 * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or the resource could not be published for other reasons
	 * @deprecated since Flow 3.0. You cannot retrieve a base path for static resources anymore, please use resource://* instead or call ResourceManager->getPublicPackageResourceUri()
	 */
	public function getStaticResourcesWebBaseUri() {
		$this->systemLogger->log('The deprecated method ResourcePublisher->getStaticResourcesWebBaseUri() has been called' . $this->getCallee() . '. You cannot retrieve a base path for static resources anymore, please use resource://* instead or call ResourceManager->getPublicPackageResourceUri().', LOG_WARNING);
		return preg_replace('/\/Packages\/$/', '/', $this->resourceManager->getCollection(ResourceManager::DEFAULT_STATIC_COLLECTION_NAME)->getTarget()->getPublicStaticResourceUri(''));
	}

	/**
	 * @return string
	 */
	protected function getCallee() {
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$backtraceStep = $backtrace[2];
		if (isset($backtraceStep['file']) && strpos($backtraceStep['file'], 'DependencyProxy') !== FALSE) {
			$backtraceStep = $backtrace[3];
		}

		if (isset($backtraceStep['file'])) {
			return sprintf(' in file %s, line %s', $backtraceStep['file'], $backtraceStep['line']);
		} else {
			return '';
		}
	}
}
