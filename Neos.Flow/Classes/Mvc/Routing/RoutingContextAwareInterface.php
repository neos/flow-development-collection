<?php
namespace Neos\Flow\Mvc\Routing;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Routing\Dto\RoutingContext;

/**
 * TODO document
 */
interface RoutingContextAwareInterface
{

    /**
     * @param RoutingContext $routingContext
     * @return void
     */
    public function setRoutingContext(RoutingContext $routingContext);
}
