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
use Neos\Flow\Mvc\Controller\ControllerContext;

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
     * @param ControllerContext $controllerContext Context of the controller associated with this view
     * @return void
     * @api
     */
    public function setControllerContext(ControllerContext $controllerContext);

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
     * Tells if the view implementation can render the view for the given context.
     *
     * @param ControllerContext $controllerContext
     * @return boolean TRUE if the view has something useful to display, otherwise FALSE
     */
    public function canRender(ControllerContext $controllerContext);

    /**
     * Renders the view
     *
     * @return string The rendered view
     * @api
     */
    public function render();

    /**
     * Factory method to create an instance with given options.
     *
     * @param array $options
     * @return ViewInterface
     */
    public static function createWithOptions(array $options);
}
