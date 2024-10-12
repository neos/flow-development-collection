<?php

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Flow\Mvc\Routing;

use Neos\Flow\ObjectManagement\ObjectManagerInterface;

class AttributeRoutesProviderFactory implements RoutesProviderFactoryInterface
{
    public function __construct(
        public readonly ObjectManagerInterface $objectManager,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createRoutesProvider(array $options): RoutesProviderInterface
    {
        return new AttributeRoutesProvider(
            $this->objectManager,
            $options['classNames'] ?? [],
        );
    }
}
