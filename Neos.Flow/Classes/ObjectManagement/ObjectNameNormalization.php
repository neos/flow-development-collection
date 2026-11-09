<?php

declare(strict_types=1);

namespace Neos\Flow\ObjectManagement;

/**
 * Ensures object names are not used with backslashes.
 * For now logging deprecations, later might throw.
 */
trait ObjectNameNormalization
{
    /**
     * Object and class names are registered without a leading backslash, but may be passed
     * as fully qualified for example from Configuration.
     */
    protected function normalizeObjectName(string $objectName): string
    {
        if (str_starts_with($objectName, '\\')) {
            $this->logger->warning('DEPRECATED: Requested object "' . $objectName . '" with leading backslash. Remove the backslash for future compatiblity.', debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5));
            return ltrim($objectName, '\\');
        }

        return $objectName;
    }
}
