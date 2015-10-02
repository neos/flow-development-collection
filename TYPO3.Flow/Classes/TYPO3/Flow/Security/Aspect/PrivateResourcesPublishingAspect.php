<?php
namespace TYPO3\Flow\Security\Aspect;

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

/**
 * An aspect which cares for a special publishing of private resources.
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class PrivateResourcesPublishingAspect
{
    /**
     * @var \TYPO3\Flow\Security\Context
     * @Flow\Inject
     */
    protected $securityContext;

    /**
     * @var \TYPO3\Flow\Session\SessionInterface
     * @Flow\Inject
     */
    protected $session;

    /**
     * @var \TYPO3\Flow\Utility\Environment
     * @Flow\Inject
     */
    protected $environment;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var \TYPO3\Flow\Security\Authorization\Resource\AccessRestrictionPublisherInterface
     * @Flow\Inject
     */
    protected $accessRestrictionPublisher;

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
     * Returns the web URI to be used to publish the specified persistent resource
     *
     * @Flow\Around("setting(TYPO3.Flow.security.enable) && method(TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget->buildPersistentResourceWebUri())")
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
     * @return mixed Result of the target method, a rewritten private resource URI or FALSE on error
     * @todo Rewrite of the resource title should be done by general string to uri rewrite function from somewhere else
     */
    public function rewritePersistentResourceWebUriForPrivateResources(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint)
    {
        $resource = $joinPoint->getMethodArgument('resource');
        $filename = $resource->getFilename();
        /** @var $configuration \TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration */
        $configuration = $resource->getPublishingConfiguration();

        if ($configuration === null || ($configuration instanceof \TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration) === false) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }

        $result = false;

        $allowedRoles = $configuration->getAllowedRoles();

        if (count(array_intersect($allowedRoles, $this->securityContext->getRoles())) > 0) {
            $privatePathSegment = $this->session->getID();
            if ($this->settings['resource']['publishing']['fileSystem']['mirrorMode'] === 'link') {
                $privatePathSegment = \TYPO3\Flow\Utility\Files::concatenatePaths(array($privatePathSegment, $allowedRoles[0]));
            }

            $rewrittenFilename = ($filename === '' || $filename === null) ? '' : '/' . preg_replace(array('/ /', '/_/', '/[^-a-z0-9.]/i'), array('-', '-', ''), $filename);
            $result = \TYPO3\Flow\Utility\Files::concatenatePaths(array($joinPoint->getProxy()->getResourcesBaseUri(), 'Persistent/', $privatePathSegment, $resource->getResourcePointer()->getHash() . $rewrittenFilename));
        }

        return $result;
    }

    /**
     * Returns the publish path and filename to be used to publish the specified persistent resource
     *
     * @Flow\Around("method(TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget->buildPersistentResourcePublishPathAndFilename()) && setting(TYPO3.Flow.security.enable)")
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
     * @return mixed Result of the target method
     */
    public function rewritePersistentResourcePublishPathAndFilenameForPrivateResources(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint)
    {
        $resource = $joinPoint->getMethodArgument('resource');
        /** @var $configuration \TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration */
        $configuration = $resource->getPublishingConfiguration();
        $returnFilename = $joinPoint->getMethodArgument('returnFilename');

        if ($configuration === null || ($configuration instanceof \TYPO3\Flow\Security\Authorization\Resource\SecurityPublishingConfiguration) === false) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }

        $publishingPath = false;

        $allowedRoles = $configuration->getAllowedRoles();

        if (count(array_intersect($allowedRoles, $this->securityContext->getRoles())) > 0) {
            $publishingPath = \TYPO3\Flow\Utility\Files::concatenatePaths(array($joinPoint->getProxy()->getResourcesPublishingPath(), 'Persistent/', $this->session->getID())) . '/';
            $filename = $resource->getResourcePointer()->getHash() . '.' . $resource->getFileExtension();

            \TYPO3\Flow\Utility\Files::createDirectoryRecursively($publishingPath);
            $this->accessRestrictionPublisher->publishAccessRestrictionsForPath($publishingPath);

            if ($this->settings['resource']['publishing']['fileSystem']['mirrorMode'] === 'link') {
                foreach ($allowedRoles as $role) {
                    $roleDirectory = \TYPO3\Flow\Utility\Files::concatenatePaths(array($this->environment->getPathToTemporaryDirectory(), 'PrivateResourcePublishing/', $role));
                    \TYPO3\Flow\Utility\Files::createDirectoryRecursively($roleDirectory);

                    if (file_exists($publishingPath . $role)) {
                        if (\TYPO3\Flow\Utility\Files::is_link(\TYPO3\Flow\Utility\Files::concatenatePaths(array($publishingPath, $role))) && (realpath(\TYPO3\Flow\Utility\Files::concatenatePaths(array($publishingPath, $role))) === $roleDirectory)) {
                            continue;
                        }
                        unlink($publishingPath . $role);
                        symlink($roleDirectory, \TYPO3\Flow\Utility\Files::concatenatePaths(array($publishingPath, $role)));
                    } else {
                        symlink($roleDirectory, \TYPO3\Flow\Utility\Files::concatenatePaths(array($publishingPath, $role)));
                    }
                }
                $publishingPath = \TYPO3\Flow\Utility\Files::concatenatePaths(array($publishingPath, $allowedRoles[0])) . '/';
            }

            if ($returnFilename === true) {
                $publishingPath = \TYPO3\Flow\Utility\Files::concatenatePaths(array($publishingPath, $filename));
            }
        }

        return $publishingPath;
    }

    /**
     * Unpublishes a private resource from all private user directories
     *
     * @Flow\After("method(TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget->unpublishPersistentResource()) && setting(TYPO3.Flow.security.enable)")
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
     * @return mixed Result of the target method
     * @todo implement this method
     */
    public function unpublishPrivateResource(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint)
    {
        return false;
    }
}
