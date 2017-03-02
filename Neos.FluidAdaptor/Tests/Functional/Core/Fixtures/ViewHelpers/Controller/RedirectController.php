<?php
namespace Neos\FluidAdaptor\Tests\Functional\Core\Fixtures\ViewHelpers\Controller;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\Widget\AbstractWidgetController;

/**
 * Controller of the redirect widget
 */
class RedirectController extends AbstractWidgetController
{
    /**
     * Initial action (showing different links)
     *
     * @return void
     */
    public function indexAction()
    {
    }

    /**
     * The target action for redirects/forwards
     *
     * @param string $parameter
     * @return void
     */
    public function targetAction($parameter = null)
    {
        $this->view->assign('parameter', $parameter);
    }

    /**
     * @param integer $delay
     * @param string $parameter
     * @param boolean $otherController
     * @return void
     */
    public function redirectTestAction($delay = 0, $parameter = null, $otherController = false)
    {
        $this->addFlashMessage('Redirect triggered!');
        $arguments = array();
        if ($parameter !== null) {
            $arguments['parameter'] = $parameter . ', via redirect';
        }
        $action = $otherController ? 'index' : 'target';
        $controller = $otherController ? 'Paginate' : null;
        $package = $otherController ? 'Neos.FluidAdaptor\ViewHelpers\Widget' : null;
        $this->redirect($action, $controller, $package, $arguments, $delay);
    }

    /**
     * @param string $parameter
     * @param boolean $otherController
     * @return void
     */
    public function forwardTestAction($parameter = null, $otherController = false)
    {
        $this->addFlashMessage('Forward triggered!');
        $arguments = array();
        if ($parameter !== null) {
            $arguments['parameter'] = $parameter . ', via forward';
        }
        $action = $otherController ? 'index' : 'target';
        $controller = $otherController ? 'Standard' : null;
        $package = $otherController ? 'Neos.Flow\Mvc' : null;
        $this->forward($action, $controller, $package, $arguments);
    }
}
