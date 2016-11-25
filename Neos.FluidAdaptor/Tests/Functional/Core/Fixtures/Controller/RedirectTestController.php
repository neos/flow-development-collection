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
 * the redirect/forward behavior of widgets.
 */
class RedirectTestController extends ActionController
{
    /**
     * Includes the widget through its Index.html template and renders it.
     *
     * @return string
     */
    public function indexAction()
    {
    }
}
