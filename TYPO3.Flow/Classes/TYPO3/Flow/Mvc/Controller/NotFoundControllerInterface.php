<?php
namespace TYPO3\Flow\Mvc\Controller;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Interface for "not found" controllers
 * @deprecated since Flow 2.0. Use the "renderingGroups" options of the exception handler configuration instead
 */
interface NotFoundControllerInterface extends ControllerInterface
{
    /**
     * Sets an exception with technical information about the reason why
     * no controller could be resolved.
     *
     * @param \TYPO3\Flow\Mvc\Controller\Exception $exception
     * @return void
     */
    public function setException(\TYPO3\Flow\Mvc\Controller\Exception $exception);
}
