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
    public function __construct(
        public readonly string $uriPattern,
        public readonly ?string $name = null,
        public readonly array $httpMethods = [],
        public readonly array $defaults = [],
    ) {
    }
}
