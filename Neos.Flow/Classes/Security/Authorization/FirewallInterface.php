<?php
namespace Neos\Flow\Security\Authorization;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\ActionRequest;

/**
 * Contract for firewall
 *
 */
interface FirewallInterface
{
    /**
     * Analyzes a request against the configured firewall rules and blocks
     * any illegal request.
     *
     * @param ActionRequest $request The request to be analyzed
     * @return void
     */
    public function blockIllegalRequests(ActionRequest $request);
}
