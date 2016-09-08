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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\View\ViewInterface;
use TYPO3\Fluid\View\TemplateView;

/**
 * A Special Case of a Controller: If no controller has been specified in the
 * request, this controller is chosen.
 */
class StandardController extends ActionController
{
    /**
     * Overrides the standard resolveView method
     *
     * @return ViewInterface $view The view
     */
    protected function resolveView()
    {
        $view = new TemplateView();
        $view->setControllerContext($this->controllerContext);
        $view->setTemplatePathAndFilename(FLOW_PATH_FLOW . 'Resources/Private/Mvc/StandardView_Template.html');
        return $view;
    }

    /**
     * Displays the default view
     *
     * @return string
     */
    public function indexAction()
    {
        if (!$this->request instanceof ActionRequest) {
            return
                "\nWelcome to Flow!\n\n" .
                "This is the default view of the Flow MVC object. You see this message because no \n" .
                "other view is available. Please refer to the Developer's Guide for more information \n" .
                "how to create and configure one.\n\n" .
                "Have fun! The Flow Development Team\n";
        }
    }
}
