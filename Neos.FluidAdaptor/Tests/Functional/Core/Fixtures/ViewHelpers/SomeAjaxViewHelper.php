<?php
namespace Neos\FluidAdaptor\Tests\Functional\Core\Fixtures\ViewHelpers;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\Widget\AbstractWidgetViewHelper;

/**
 * A view helper for the test AJAX widget
 */
class SomeAjaxViewHelper extends AbstractWidgetViewHelper
{
    /**
     * @var boolean
     */
    protected $ajaxWidget = true;

    /**
     * @Flow\Inject
     * @var \Neos\FluidAdaptor\Tests\Functional\Core\Fixtures\ViewHelpers\Controller\SomeAjaxController
     */
    protected $controller;


    /**
     * Initialize the arguments.
     *
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('option1', 'string', 'Option for testing if parameters can be passed', false, '');
        $this->registerArgument('option2', 'string', 'Option for testing if parameters can be passed', false, '');
    }

    /**
     * The actual render method does nothing more than initiating the sub request
     * which invokes the controller.
     *
     * @return string
     * @throws \Neos\Flow\Mvc\Exception\InfiniteLoopException
     * @throws \Neos\FluidAdaptor\Core\Widget\Exception\InvalidControllerException
     * @throws \Neos\FluidAdaptor\Core\Widget\Exception\MissingControllerException
     */
    public function render(): string
    {
        $response = $this->initiateSubRequest();
        return $response;
    }
}
