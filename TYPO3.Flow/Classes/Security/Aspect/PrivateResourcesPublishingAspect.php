<?php
namespace TYPO3\FLOW3\Security\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An aspect which cares for a special publishing of private resources.
 *
 * @FLOW3\Scope("singleton")
 * @FLOW3\Aspect
 */
class PrivateResourcesPublishingAspect {

	/**
	 * @var \TYPO3\FLOW3\Security\Context
	 * @FLOW3\Inject
	 */
	protected $securityContext;

	/**
	 * @var \TYPO3\FLOW3\Session\SessionInterface
	 * @FLOW3\Inject
	 */
	protected $session;

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 * @FLOW3\Inject
	 */
	protected $environment;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \TYPO3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface
	 * @FLOW3\Inject
	 */
	protected $accessRestrictionPublisher;

	/**
	 * Injects the settings of this package
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Returns the web URI to be used to publish the specified persistent resource
	 *
	 * @FLOW3\Around("setting(TYPO3.FLOW3.security.enable) && method(TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget->buildPersistentResourceWebUri())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return mixed Result of the target method, a rewritten private resource URI or FALSE on error
	 * @todo Rewrite of the resource title should be done by general string to uri rewrite function from somewhere else
	 */
	public function rewritePersistentResourceWebUriForPrivateResources(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$resource = $joinPoint->getMethodArgument('resource');
		$filename = $resource->getFilename();
		$configuration = $resource->getPublishingConfiguration();

		if ($configuration === NULL || ($configuration instanceof \TYPO3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration) === FALSE) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		}

		$result = FALSE;

		$allowedRoles = $configuration->getAllowedRoles();

		if (count(array_intersect($allowedRoles, $this->securityContext->getRoles())) > 0) {
			$privatePathSegment = $this->session->getID();
			if ($this->settings['resource']['publishing']['fileSystem']['mirrorMode'] === 'link') $privatePathSegment = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($privatePathSegment, $allowedRoles[0]));

			$rewrittenFilename = ($filename === '' || $filename === NULL) ? '' : '/' . preg_replace(array('/ /', '/_/', '/[^-a-z0-9.]/i'), array('-', '-', ''), $filename);
			$result = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($joinPoint->getProxy()->getResourcesBaseUri(), 'Persistent/', $privatePathSegment, $resource->getResourcePointer()->getHash() . $rewrittenFilename));
		}

		return $result;
	}

	/**
	 * Returns the publish path and filename to be used to publish the specified persistent resource
	 *
	 * @FLOW3\Around("method(TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget->buildPersistentResourcePublishPathAndFilename()) && setting(TYPO3.FLOW3.security.enable)")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return mixed Result of the target method
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResources(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$resource = $joinPoint->getMethodArgument('resource');
		$configuration = $resource->getPublishingConfiguration();
		$returnFilename = $joinPoint->getMethodArgument('returnFilename');

		if ($configuration === NULL || ($configuration instanceof \TYPO3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration) === FALSE) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		}

		$publishingPath = FALSE;

		$allowedRoles = $configuration->getAllowedRoles();

		if (count(array_intersect($allowedRoles, $this->securityContext->getRoles())) > 0) {
			$publishingPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($joinPoint->getProxy()->getResourcesPublishingPath(), 'Persistent/', $this->session->getID())) . '/';
			$filename = $resource->getResourcePointer()->getHash() . '.' . $resource->getFileExtension();

			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($publishingPath);
			$this->accessRestrictionPublisher->publishAccessRestrictionsForPath($publishingPath);

			if ($this->settings['resource']['publishing']['fileSystem']['mirrorMode'] === 'link') {

				foreach ($allowedRoles as $role) {
					$roleDirectory = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($this->environment->getPathToTemporaryDirectory(), 'PrivateResourcePublishing/', $role));
					\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($roleDirectory);

					if (file_exists($publishingPath . $role)) {
						if (\TYPO3\FLOW3\Utility\Files::is_link(\TYPO3\FLOW3\Utility\Files::concatenatePaths(array($publishingPath, $role))) && (realpath(\TYPO3\FLOW3\Utility\Files::concatenatePaths(array($publishingPath, $role))) === $roleDirectory)) {
							continue;
						}
						unlink($publishingPath . $role);
						symlink($roleDirectory, \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($publishingPath, $role)));
					} else {
						symlink($roleDirectory, \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($publishingPath, $role)));
					}
				}
				$publishingPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($publishingPath, $allowedRoles[0])) . '/';
			}

			if ($returnFilename === TRUE) $publishingPath = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($publishingPath, $filename));
		}

		return $publishingPath;
	}

	/**
	 * Unpublishes a private resource from all private user directories
	 *
	 * @FLOW3\After("method(TYPO3\FLOW3\Resource\Publishing\FileSystemPublishingTarget->unpublishPersistentResource()) && setting(TYPO3.FLOW3.security.enable)")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return mixed Result of the target method
	 * @todo implement this method
	 */
	public function unpublishPrivateResource(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		return FALSE;
	}
}

?>
