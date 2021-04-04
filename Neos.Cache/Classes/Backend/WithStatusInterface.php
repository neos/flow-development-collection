<?php
namespace Neos\Cache\Backend;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Result;

/**
 * A contract for a Cache Backend which allows to retrieve its status
 *
 * @api
 */
interface WithStatusInterface
{

    /**
     * Returns the status of the cache backend
     *
     * This can be used to test the cache configuration. By default that method is only invoked from CLI
     * so it does not have to be extremely fast and the result can be verbose.
     *
     * @return Result
     * @api
     */
    public function getStatus(): Result;
}
