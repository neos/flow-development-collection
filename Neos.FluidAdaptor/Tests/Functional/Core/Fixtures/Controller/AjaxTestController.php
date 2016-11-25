<?php
namespace Neos\FluidAdaptor\Tests\Functional\Core\Fixtures\Controller;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Controller\ActionController;

/**
 * This is a regular action controller which serves as the starting point for testing
 * the AJAX widget functionality. It has nothing to do with the actual AJAX call. It
 * simulates the place where you'd integrate an AJAX widget and helps us rendering
 * a valid URI we can use for sending an AJAX request to the test widget.
 */
class AjaxTestController extends ActionController
{
    /**
     * Includes the widget through its Index.html template and renders it.
     *
     * @return string
     */
    public function indexAction()
    {
    }

    /**
     * Renders and returns the URI pointing to the widget for an AJAX call.
     *
     * @return string
     */
    public function widgetUriAction()
    {
    }
}
