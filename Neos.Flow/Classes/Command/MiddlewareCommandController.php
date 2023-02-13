<?php
namespace Neos\Flow\Command;

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
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Mvc\Routing\Route;
use Neos\Utility\PositionalArraySorter;

/**
 * Command controller for PSR-15 (middleware) related commands
 */
final class MiddlewareCommandController extends CommandController
{
    /**
     * @Flow\InjectConfiguration(path="http.middlewares")
     * @var array
     */
    protected $chainConfiguration;

    /**
     * Lists all configured middleware components in the order they will be executed
     */
    public function listCommand(): void
    {
        $orderedChainConfiguration = new PositionalArraySorter($this->chainConfiguration);
        $this->outputLine('<b>Currently configured middlewares:</b>');
        $rows = [];
        $index = 0;
        /** @var Route $route */
        foreach ($orderedChainConfiguration->toArray() as $middlewareName => $middlewareConfiguration) {
            $rows[] = [
                '#' => ++ $index,
                'name' => $middlewareName,
                'className' => $middlewareConfiguration['middleware'],
            ];
        }
        $this->output->outputTable($rows, ['#', 'Name', 'Class name']);
    }
}
