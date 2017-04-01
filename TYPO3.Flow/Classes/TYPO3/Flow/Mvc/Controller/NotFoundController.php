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

/**
 * A Special Case of a Controller: If no controller could be resolved this
 * controller is chosen.
 *
 * @deprecated since Flow 2.0. Use the "renderingGroups" options of the exception handler configuration instead
 */
class NotFoundController extends \TYPO3\Flow\Mvc\Controller\AbstractController implements \TYPO3\Flow\Mvc\Controller\NotFoundControllerInterface
{
    /**
     * @var \TYPO3\Flow\Mvc\View\NotFoundView
     */
    protected $notFoundView;

    /**
     * @var \TYPO3\Flow\Mvc\Controller\Exception
     */
    protected $exception;

    /**
     * Injects the NotFoundView.
     *
     * @param \TYPO3\Flow\Mvc\View\NotFoundView $notFoundView
     * @return void
     * @api
     */
    public function injectNotFoundView(\TYPO3\Flow\Mvc\View\NotFoundView $notFoundView)
    {
        $this->notFoundView = $notFoundView;
    }

    /**
     * Sets the controller exception
     *
     * @param \TYPO3\Flow\Mvc\Controller\Exception $exception
     * @return void
     */
    public function setException(\TYPO3\Flow\Mvc\Controller\Exception $exception)
    {
        $this->exception = $exception;
    }

    /**
     * Processes a generic request and fills the response with the default view
     *
     * @param \TYPO3\Flow\Mvc\RequestInterface $request The request object
     * @param \TYPO3\Flow\Mvc\ResponseInterface $response The response, modified by this handler
     * @return void
     * @api
     */
    public function processRequest(\TYPO3\Flow\Mvc\RequestInterface $request, \TYPO3\Flow\Mvc\ResponseInterface $response)
    {
        $this->initializeController($request, $response);
        $this->notFoundView->setControllerContext($this->controllerContext);
        if ($this->exception !== null) {
            $this->notFoundView->assign('errorMessage', $this->exception->getMessage());
        }
        switch (get_class($request)) {
            case 'TYPO3\Flow\Mvc\ActionRequest':
                $response->setStatus(404);
                $response->setContent($this->notFoundView->render());
                break;
            default:
                $response->setContent("\nUnknown command\n\n");
        }
    }
}
