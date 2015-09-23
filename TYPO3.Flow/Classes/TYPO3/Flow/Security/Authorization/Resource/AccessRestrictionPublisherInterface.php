<?php
namespace TYPO3\Flow\Security\Authorization\Resource;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
