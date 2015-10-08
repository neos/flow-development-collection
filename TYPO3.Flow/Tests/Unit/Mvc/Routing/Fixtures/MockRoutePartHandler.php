<?php
namespace TYPO3\Flow\Mvc\Routing\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A mock RoutePartHandler
 *
 */
class MockRoutePartHandler extends \TYPO3\Flow\Mvc\Routing\DynamicRoutePart
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
