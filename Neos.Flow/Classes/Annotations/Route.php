<?php
declare(strict_types=1);

namespace Neos\Flow\Annotations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Adds a route configuration to a method
 *
 * This is a convenient way to add routes in project code
 * but should not be used in libraries/packages that shall be
 * configured for different use cases.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target({"METHOD"})
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Route
{
    /**
     * @param string $uriPattern The uri-pattern for the route without leading '/'. Must not contain `{@action}` or `{@controller}`.
     * @param string|null $name (default null) The name ouf the route as it shows up in the route:list command
     * @param array $httpMethods (default []) List of http verbs like 'GET', 'POST', 'PUT', 'DELETE', if not specified 'any' is used
     * @param array $defaults (default []) Values to set for this route. Dan define arguments but also specify the `@format` if required.
     */
    public function __construct(
        public readonly string $uriPattern,
        public readonly ?string $name = null,
        public readonly array $httpMethods = [],
        public readonly array $defaults = [],
    ) {
        if (str_contains($uriPattern, '{@controller}') || str_contains($uriPattern, '{@action}')) {
            throw new \DomainException(sprintf('It is not allowed to override {@controller} or {@action} in route annotations "%s"', $uriPattern), 1711129634);
        }
        if (in_array(array_keys($defaults), ['@package', '@subpackage', '@controller', '@action'])) {
            throw new \DomainException(sprintf('It is not allowed to override @package, @controller, @subpackage and @action in route annotation defaults "%s"', $uriPattern), 1711129638);
        }
    }
}
