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
 * A contract for a Cache Backend which allows to be setup
 *
 * @api
 */
interface SetupEnabledBackendInterface extends BackendInterface
{
    /**
     * TODO document
     *
     * @return Result
     * @api
     */
    public function setup(): Result;

    /**
     * TODO document
     *
     * @return Result
     * @api
     */
    public function getStatus(): Result;
}
