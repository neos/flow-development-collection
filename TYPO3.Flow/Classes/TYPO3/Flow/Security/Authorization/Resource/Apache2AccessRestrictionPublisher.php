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
