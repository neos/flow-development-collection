<?php

declare(strict_types=1);

namespace Neos\Flow\Mvc\Routing;

interface RoutesProviderInterface
{
    public function getRoutes(): Routes;
}
