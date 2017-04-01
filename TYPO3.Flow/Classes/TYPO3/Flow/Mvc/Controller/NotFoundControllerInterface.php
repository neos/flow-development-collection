<?php
namespace TYPO3\Flow\Mvc\Controller;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
