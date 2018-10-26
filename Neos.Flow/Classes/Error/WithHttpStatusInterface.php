<?php
namespace Neos\Flow\Error;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Marks Exceptions having a HTTP status code to return.
 */
interface WithHttpStatusInterface
{
    /**
     * Returns the HTTP status code this exception corresponds to.
     *
     * Should default to 500.
     *
     * @return integer
     * @api
     */
    public function getStatusCode();
}
