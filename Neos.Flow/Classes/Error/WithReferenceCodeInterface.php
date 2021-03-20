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
 * Marks Exceptions having a reference code to look up.
 */
interface WithReferenceCodeInterface
{
    /**
     * Returns a code which can be communicated publicly so that whoever experiences the exception can refer
     * to it and a developer can find more information about it in the system log.
     *
     * Could be a timestamp (+ some random factor to avoid duplications) for example.
     *
     * @return string
     * @api
     */
    public function getReferenceCode();
}
