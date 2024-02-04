<?php
namespace Neos\Flow\Monitor\ChangeDetectionStrategy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Contract for a change detection strategy that allows the FileMonitor to flush all removals directly.
 *
 * @api
 */
interface StrategyWithFlushDeletedOnPathInterface
{
    /**
     * @param string $onPath
     * @param array<string,1> $filesIgnoreMask files to ignore as we are sure they exist
     * @return array<string, ChangeDetectionStrategyInterface::STATUS_DELETED>
     */
    public function flushDeletedOnPath(string $onPath, array $filesIgnoreMask): array;
}
