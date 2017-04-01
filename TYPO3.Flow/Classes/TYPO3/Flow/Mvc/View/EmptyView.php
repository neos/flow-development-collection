<?php
namespace TYPO3\Flow\Mvc\View;

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
 * An empty view - a special case.
 *
 * @deprecated since Flow 2.0. Return an empty string if you want an action to render blank
 */
final class EmptyView implements ViewInterface
{
    /**
     * Dummy method to satisfy the ViewInterface
     *
     * @param \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext
     * @return void
     * @api
     */
    public function setControllerContext(\TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext)
    {
    }

    /**
     * Dummy method to satisfy the ViewInterface
     *
     * @param string $key
     * @param mixed $value
     * @return \TYPO3\Flow\Mvc\View\EmptyView instance of $this to allow chaining
     * @api
     */
    public function assign($key, $value)
    {
        return $this;
    }

    /**
     * Dummy method to satisfy the ViewInterface
     *
     * @param array $values
     * @return \TYPO3\Flow\Mvc\View\EmptyView instance of $this to allow chaining
     * @api
     */
    public function assignMultiple(array $values)
    {
        return $this;
    }

    /**
     * This view can be used in any case.
     *
     * @param \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext
     * @return boolean TRUE
     */
    public function canRender(\TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext)
    {
        return true;
    }

    /**
     * Renders the empty view
     *
     * @return string An empty string
     */
    public function render()
    {
        return '';
    }

    /**
     * A magic call method.
     *
     * Because this empty view is used as a Special Case in situations when no matching
     * view is available, it must be able to handle method calls which originally were
     * directed to another type of view. This magic method should prevent PHP from issuing
     * a fatal error.
     *
     * @param string $methodName Name of the method
     * @param array $arguments Arguments passed to the method
     * @return void
     */
    public function __call($methodName, array $arguments)
    {
    }
}
