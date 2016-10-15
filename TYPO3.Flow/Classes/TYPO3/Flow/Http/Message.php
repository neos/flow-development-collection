<?php
namespace TYPO3\Flow\Http;

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
 * @deprecated since 3.0 Only present for backward compatibility, instantiate your own Message implementation which inherits from AbstractMessage, or use the Request or Response classes.
 * @Flow\Proxy(false)
 */
class Message extends AbstractMessage
{
    /**
     * Method stub to satisfy the prescribed presence in AbstractMessage
     * @see AbstractMessage::getStartLine
     */
    public function getStartLine()
    {
    }
}
