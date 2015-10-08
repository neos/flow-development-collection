<?php
namespace TYPO3\Flow\Monitor\ChangeDetectionStrategy;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Contract for a change detection strategy that allows the FileMonitor to mark a file deleted directly.
 *
 * @api
 */
interface StrategyWithMarkDeletedInterface
{
    /**
     * Notify the change strategy that this file was deleted and does not need to be tracked anymore.
     *
     * @param string $pathAndFilename
     * @return void
     */
    public function setFileDeleted($pathAndFilename);
}
