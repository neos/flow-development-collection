<?php
namespace Neos\RedirectHandler;

/*
 * This file is part of the Neos.RedirectHandler package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Redirect Interface
 */
interface RedirectInterface
{
    /**
     * @return string
     */
    public function getSourceUriPath();

    /**
     * @return string
     */
    public function getTargetUriPath();

    /**
     * @return integer
     */
    public function getStatusCode();

    /**
     * @return string
     */
    public function getHost();
}
