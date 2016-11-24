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
     * The actual render method does nothing more than initiating the sub request
     * which invokes the controller.
     *
     * @param string $option1 Option for testing if parameters can be passed
     * @param string $option2 Option for testing if parameters can be passed
     * @return string
     */
    public function render($option1 = '', $option2 = '')
    {
        return $this->initiateSubRequest();
    }
}
