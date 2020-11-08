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

use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\DynamicRoutePart;

/**
 * A mock RoutePartHandler
 */
class MockRoutePartHandler extends DynamicRoutePart
{

    /**
     * @var \Closure|null
     */
    private $matchValueClosure;

    /**
     * @var \Closure|null
     */
    private $resolveValueClosure;

    public function __construct(\Closure $matchValueClosure = null, \Closure $resolveValueClosure = null)
    {
        $this->matchValueClosure = $matchValueClosure;
        $this->resolveValueClosure = $resolveValueClosure;
    }

    protected function matchValue($value)
    {
        $this->value = null;
        if ($this->matchValueClosure !== null) {
            $result = call_user_func($this->matchValueClosure, $value, $this->parameters);
            if ($result instanceof MatchResult) {
                $this->value = $result->getMatchedValue();
                return $result;
            }
        }
        return false;
    }

    protected function resolveValue($value)
    {
        $this->value = null;
        if ($this->resolveValueClosure !== null) {
            $result = call_user_func($this->resolveValueClosure, $value, $this->parameters);
            if ($result instanceof ResolveResult) {
                $this->value = $result->getResolvedValue();
                return $result;
            }
        }
        return false;
    }
}
