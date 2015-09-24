<?php
namespace TYPO3\Flow\Error;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Contract for an exception handler
 *
 */
interface ExceptionHandlerInterface
{
    /**
     * Handles the given exception
     *
     * @param \Exception $exception The exception object
     * @return void
     */
    public function handleException(\Exception $exception);

    /**
     * Sets options of this exception handler
     *
     * @param array $options
     * @return void
     */
    public function setOptions(array $options);
}
