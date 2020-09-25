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

    /**
     * @var \Closure|null
     */
    private $matchClosure;

    /**
     * @var \Closure|null
     */
    private $resolveClosure;

    protected function matchValue($value)
    {
        if ($this->matchClosure !== null) {
            return call_user_func($this->matchClosure, $value);
        }
        $this->value = '_match_invoked_';
        return true;
    }

    protected function resolveValue($value)
    {
        if ($this->resolveClosure !== null) {
            return call_user_func($this->resolveClosure, $value);
        }
        $this->value = '_resolve_invoked_';
        return true;
    }

    public function setMatchClosure(?\Closure $matchClosure): void
    {
        $this->matchClosure = $matchClosure;
    }

    public function setResolveClosure(?\Closure $resolveClosure): void
    {
        $this->resolveClosure = $resolveClosure;
    }
}
