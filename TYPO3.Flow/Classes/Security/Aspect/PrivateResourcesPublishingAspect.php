<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * An aspect which cares for a special publishing of private resources.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 * @aspect
 */
class PrivateResourcesPublishingAspect {

	/**
	 * @var \F3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @var \F3\FLOW3\Session\SessionInterface
	 */
	protected $session;

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \F3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface
	 */
	protected $accessRestrictionPublisher;

	/**
	 * Injects the security context
	 *
	 * @param \F3\FLOW3\Security\Context $securityContext The security context
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSecurityContext(\F3\FLOW3\Security\Context $securityContext) {
		$this->securityContext = $securityContext;
	}

	/**
	 * Injects the session
	 *
	 * @param \F3\FLOW3\Session\SessionInterface $session The session
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSession(\F3\FLOW3\Session\SessionInterface $session) {
		$this->session = $session;
	}

	/**
	 * Injects the environment
	 *
	 * @param \F3\FLOW3\Utility\Environment $environment The environment
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectEnvironment(\F3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Injects the settings of this package
	 *
	 * @param array $settings
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Injects the access restriction publisher
	 *
	 * @param \F3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface $accessRestrictionPublisher The access restriction publisher
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectAccessRestrictionPublisher(\F3\FLOW3\Security\Authorization\Resource\AccessRestrictionPublisherInterface $accessRestrictionPublisher) {
		$this->accessRestrictionPublisher = $accessRestrictionPublisher;
	}

	/**
	 * Returns the web URI to be used to publish the specified persistent resource
	 *
	 * @around method(F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget->buildPersistentResourceWebUri()) && setting(FLOW3.security.enable)
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return mixed Result of the target method, a rewritten private resource URI or FALSE on error
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo Rewrite of the resource title should be done by general string to uri rewrite function from somewhere else
	 */
	public function rewritePersistentResourceWebUriForPrivateResources(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$resource = $joinPoint->getMethodArgument('resource');
		$filename = $resource->getFilename();
		$configuration = $resource->getPublishingConfiguration();

		if ($configuration === NULL || ($configuration instanceof \F3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration) === FALSE) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		}

		$result = FALSE;

		$allowedRoles = $configuration->getAllowedRoles();

		if (count(array_intersect($allowedRoles, $this->securityContext->getRoles())) > 0) {
			$privatePathSegment = $this->session->getID();
			if ($this->settings['resource']['publishing']['fileSystem']['mirrorMode'] === 'link') $privatePathSegment = \F3\FLOW3\Utility\Files::concatenatePaths(array($privatePathSegment, $allowedRoles[0]));

			$rewrittenFilename = ($filename === '' || $filename === NULL) ? '' : '/' . preg_replace(array('/ /', '/_/', '/[^-a-z0-9.]/i'), array('-', '-', ''), $filename);
			$result = \F3\FLOW3\Utility\Files::concatenatePaths(array($joinPoint->getProxy()->getResourcesBaseUri(), 'Persistent/', $privatePathSegment, $resource->getResourcePointer()->getHash() . $rewrittenFilename));
		}

		return $result;
	}

	/**
	 * Returns the publish path and filename to be used to publish the specified persistent resource
	 *
	 * @around method(F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget->buildPersistentResourcePublishPathAndFilename()) && setting(FLOW3.security.enable)
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return mixed Result of the target method
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function rewritePersistentResourcePublishPathAndFilenameForPrivateResources(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$resource = $joinPoint->getMethodArgument('resource');
		$configuration = $resource->getPublishingConfiguration();
		$returnFilename = $joinPoint->getMethodArgument('returnFilename');

		if ($configuration === NULL || ($configuration instanceof \F3\FLOW3\Security\Authorization\Resource\SecurityPublishingConfiguration) === FALSE) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		}

		$publishingPath = FALSE;

		$allowedRoles = $configuration->getAllowedRoles();

		if (count(array_intersect($allowedRoles, $this->securityContext->getRoles())) > 0) {
			$publishingPath = \F3\FLOW3\Utility\Files::concatenatePaths(array($joinPoint->getProxy()->getResourcesPublishingPath(), 'Persistent/', $this->session->getID())) . '/';
			$filename = $resource->getResourcePointer()->getHash() . '.' . $resource->getFileExtension();

			\F3\FLOW3\Utility\Files::createDirectoryRecursively($publishingPath);
			$this->accessRestrictionPublisher->publishAccessRestrictionsForPath($publishingPath);

			if ($this->settings['resource']['publishing']['fileSystem']['mirrorMode'] === 'link') {

				foreach ($allowedRoles as $role) {
					$roleDirectory = \F3\FLOW3\Utility\Files::concatenatePaths(array($this->environment->getPathToTemporaryDirectory(), 'PrivateResourcePublishing/', $role));
					\F3\FLOW3\Utility\Files::createDirectoryRecursively($roleDirectory);

					if (file_exists($publishingPath . $role)) {
						if (\F3\FLOW3\Utility\Files::is_link(\F3\FLOW3\Utility\Files::concatenatePaths(array($publishingPath, $role))) && (realpath(\F3\FLOW3\Utility\Files::concatenatePaths(array($publishingPath, $role))) === $roleDirectory)) {
							continue;
						}
						unlink($publishingPath . $role);
						symlink($roleDirectory, \F3\FLOW3\Utility\Files::concatenatePaths(array($publishingPath, $role)));
					} else {
						symlink($roleDirectory, \F3\FLOW3\Utility\Files::concatenatePaths(array($publishingPath, $role)));
					}
				}
				$publishingPath = \F3\FLOW3\Utility\Files::concatenatePaths(array($publishingPath, $allowedRoles[0])) . '/';
			}

			if ($returnFilename === TRUE) $publishingPath = \F3\FLOW3\Utility\Files::concatenatePaths(array($publishingPath, $filename));
		}

		return $publishingPath;
	}

	/**
	 * Unpublishes a private resource from all private user directories
	 *
	 * @after method(F3\FLOW3\Resource\Publishing\FileSystemPublishingTarget->unpublishPersistentResource()) && setting(FLOW3.security.enable)
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return mixed Result of the target method
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo implement this method
	 */
	public function unpublishPrivateResource(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return FALSE;
	}
}

?>
