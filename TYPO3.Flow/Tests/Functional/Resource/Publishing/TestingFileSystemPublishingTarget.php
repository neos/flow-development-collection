<?php
namespace TYPO3\Flow\Tests\Functional\Resource\Publishing;

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
 * Stub filesystem publishing target, hardcoding the Resource base URI to an arbitary,
 * fixed URI.
 *
 * In Objects.yaml for testing it is configured that this class is taken instead
 * of the normal FileSystemPublishingTarget.
 *
 */
class TestingFileSystemPublishingTarget extends \TYPO3\Flow\Resource\Publishing\FileSystemPublishingTarget
{
    /**
     * Always returns a fixed base URI of http://baseuri/_Resources/
     *
     * @return void
     */
    protected function detectResourcesBaseUri()
    {
        $this->resourcesBaseUri = 'http://baseuri/_Resources/';
    }
}
