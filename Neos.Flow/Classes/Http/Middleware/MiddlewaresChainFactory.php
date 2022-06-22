<?php
declare(strict_types=1);

namespace Neos\Flow\Http\Middleware;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\PositionalArraySorter;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Creates a new MiddlewaresChain according to the specified settings
 *
 * @Flow\Scope("singleton")
 */
class MiddlewaresChainFactory
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param array $chainConfiguration
     * @return MiddlewaresChain
     * @throws Exception
     */
    public function create(array $chainConfiguration): MiddlewaresChain
    {
        $arraySorter = new PositionalArraySorter($chainConfiguration);
        $sortedChainConfiguration = $arraySorter->toArray();

        $middlewaresChain = [];
        foreach ($sortedChainConfiguration as $middlewareName => $configuration) {
            if (!isset($configuration['middleware'])) {
                throw new Exception(sprintf('Middleware chain could not be created because no middleware class name is configured for middleware "%s"', $middlewareName), 1401718283);
            }
            $middleware = $this->objectManager->get($configuration['middleware']);
            if (!$middleware instanceof MiddlewareInterface) {
                throw new Exception(sprintf('Middleware chain could not be created because the class "%s" does not implement the MiddlewareInterface in middleware "%s"', $configuration['middleware'], $middlewareName), 1401718283);
            }
            $middlewaresChain[] = $middleware;
        }

        return new MiddlewaresChain($middlewaresChain);
    }
}
