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
final readonly class Route
{
    /**
     * Magic route values cannot be set as default nor be contained as segments like `{\@action}` or `{\@controller}` in the uriPattern.
     * The magic route value `\@format` is allowed if necessary.
     */
    private const PRESERVED_DEFAULTS = ['@package', '@subpackage', '@controller', '@action'];

    /**
     * @param string $uriPattern The uri-pattern for the route without leading '/'. Might contain route values in the form of `path/{foo}`
     * @param string $name The suffix of the route name as shown in `route:list` (defaults to the action name: "My.Package :: Site :: index")
     * @param array $httpMethods List of uppercase http verbs like 'GET', 'POST', 'PUT', 'DELETE', if not specified any request method will be matched
     * @param array $defaults Values to set for this route
     */
    public function __construct(
        public string $uriPattern,
        public string $name = '',
        public array $httpMethods = [],
        public array $defaults = [],
    ) {
        if ($uriPattern === '' || str_starts_with($uriPattern, '/')) {
            throw new \DomainException(sprintf('Uri pattern must not be empty or begin with a slash: "%s"', $uriPattern), 1711529592);
        }
        foreach ($httpMethods as $httpMethod) {
            if ($httpMethod === '' || ctype_lower($httpMethod)) {
                throw new \DomainException(sprintf('Http method must not be empty or be lower case: "%s"', $httpMethod), 1711530485);
            }
        }
        foreach (self::PRESERVED_DEFAULTS as $preservedDefaultName) {
            if (str_contains($uriPattern, sprintf('{%s}', $preservedDefaultName))) {
                throw new \DomainException(sprintf('It is not allowed to use "%s" in the uri pattern "%s"', $preservedDefaultName, $uriPattern), 1711129634);
            }
            if (array_key_exists($preservedDefaultName, $defaults)) {
                throw new \DomainException(sprintf('It is not allowed to override "%s" as default', $preservedDefaultName), 1711129638);
            }
        }
    }
}
