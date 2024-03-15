<?php
namespace Neos\Flow\Mvc\View;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Interface of a view
 *
 * @api
 */
interface ViewInterface
{
    /**
     * Sets the current controller context
     *
     * @deprecated if you absolutely need access to the current request please assign a variable.
     *             when using the action controller the request is directly available at "request"
     * @param ControllerContext $controllerContext Context of the controller associated with this view
     * @return void
     */
    // public function setControllerContext(ControllerContext $controllerContext);

    /**
     * Add a variable to the view data collection.
     * Can be chained, so $this->view->assign(..., ...)->assign(..., ...); is possible
     *
     * @param string $key Key of variable
     * @param mixed $value Value of object
     * @return ViewInterface an instance of $this, to enable chaining
     * @api
     */
    public function assign($key, $value);

    /**
     * Add multiple variables to the view data collection
     *
     * @param array $values array in the format array(key1 => value1, key2 => value2)
     * @return ViewInterface an instance of $this, to enable chaining
     * @api
     */
    public function assignMultiple(array $values);

    /**
     * Renders the view
     *
     * @api
     */
    public function render(): ResponseInterface|StreamInterface;

    /**
     * Factory method to create an instance with given options.
     *
     * @param array $options
     * @return ViewInterface
     */
    public static function createWithOptions(array $options);
}
