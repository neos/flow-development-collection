<?php

namespace Neos\Flow\Mvc\Routing;

class CombinedRoutesProvider implements RoutesProviderInterface
{
    public function __construct(
        public readonly ConfigurationRoutesProvider $configurationRoutesProvider,
        public readonly AnnotationRoutesProvider $annotationRoutesProvider,
    ) {
    }

    public function getRoutes(): Routes
    {
        return $this->annotationRoutesProvider->getRoutes()->merge($this->configurationRoutesProvider->getRoutes());
    }
}
