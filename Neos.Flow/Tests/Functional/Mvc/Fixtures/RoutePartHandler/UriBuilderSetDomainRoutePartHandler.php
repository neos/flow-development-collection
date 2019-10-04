<?php

namespace Neos\Flow\Tests\Functional\Mvc\Fixtures\RoutePartHandler;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Mvc\Routing\DynamicRoutePart;
use Neos\Flow\Tests\Functional\Mvc\UriBuilderTest;

/**
 * part of the test fixture for {@see UriBuilderTest}
 *
 * @Flow\Scope("singleton")
 */
class UriBuilderSetDomainRoutePartHandler extends DynamicRoutePart
{
    protected function resolveValue($foo)
    {
        $uriConstraints = UriConstraints::create();
        $uriConstraints = $uriConstraints->withHost('my-host');

        return new ResolveResult('my-path', $uriConstraints);
    }
}
