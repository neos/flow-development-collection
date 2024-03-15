<?php

declare(strict_types=1);

namespace Neos\Flow\Mvc\Routing;

/**
 * Supplier for lazily fetching the routes for the router.
 *
 * This layer of abstraction avoids having to parse the routes for every request.
 * The router will only request the routes if it comes across a route it hasn't seen (i.e. cached) before.
 *
 * @internal
 */
interface RoutesProviderWithOptionsInterface extends RoutesProviderInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function withOptions(array $options): static;
}
