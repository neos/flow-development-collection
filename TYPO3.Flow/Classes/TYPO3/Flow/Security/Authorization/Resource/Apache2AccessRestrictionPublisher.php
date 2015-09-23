<?php
namespace TYPO3\Flow\Security\Authorization\Resource;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * An access restriction publisher that publishes .htaccess files to configure apache2 restrictions
 *
 * @Flow\Scope("singleton")
 */
class Apache2AccessRestrictionPublisher implements \TYPO3\Flow\Security\Authorization\Resource\AccessRestrictionPublisherInterface
{
    /**
     * Publishes an Apache2 .htaccess file which allows access to the given directory only for the current session remote ip
     *
     * @param string $path The path to publish the restrictions for
     * @return void
     */
    public function publishAccessRestrictionsForPath($path)
    {
        $remoteAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

        if ($remoteAddress !== null) {
            $content = "Deny from all\nAllow from " . $remoteAddress;
            file_put_contents($path . '.htaccess', $content);
        }
    }
}
