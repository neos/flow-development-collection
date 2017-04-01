<?php
namespace TYPO3\Flow\Security\Authorization\Resource;

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
 * Interface for access restriction publishers
 *
 */
interface AccessRestrictionPublisherInterface
{
    /**
     * Publishes access restrictions for file path.
     * This could be a e.g. .htaccess file to deny public access for the directory or its files
     *
     * @param string $path The path to publish the restrictions for
     * @return void
     */
    public function publishAccessRestrictionsForPath($path);
}
