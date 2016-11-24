<?php
namespace Neos\Flow\Mvc\Routing\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Routing\DynamicRoutePart;

/**
 * A mock RoutePartHandler
 */
class MockRoutePartHandler extends DynamicRoutePart
{
    protected function matchValue($value)
    {
        $this->value = '_match_invoked_';
        return true;
    }

    protected function resolveValue($value)
    {
        $this->value = '_resolve_invoked_';
        return true;
    }
}
