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


use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Mvc\RequestInterface;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Annotations as Flow;

/**
 * The controller context holds information about the request, response, arguments
 * and further details of a controller. Instances of this class act as a container
 * for conveniently passing the information to other classes who need it, usually
 * views being views or view helpers.
 *
 * @api
 */
class ControllerContext
{
    /**
     * @var \TYPO3\Flow\Mvc\RequestInterface
     */
    protected $request;

    /**
     * @var \TYPO3\Flow\Http\Response
     */
    protected $response;

    /**
     * @var \TYPO3\Flow\Mvc\Controller\Arguments
     */
    protected $arguments;

    /**
     * @var \TYPO3\Flow\Mvc\Routing\UriBuilder
     */
    protected $uriBuilder;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Mvc\FlashMessageContainer
     */
    protected $flashMessageContainer;

    /**
     * Constructs this context
     *
     * @param \TYPO3\Flow\Mvc\RequestInterface $request
     * @param \TYPO3\Flow\Http\Response $response
     * @param \TYPO3\Flow\Mvc\Controller\Arguments $arguments
     * @param \TYPO3\Flow\Mvc\Routing\UriBuilder $uriBuilder
     */
    public function __construct(RequestInterface $request, Response $response, Arguments $arguments, UriBuilder $uriBuilder)
    {
        $this->request = $request;
        $this->response = $response;
        $this->arguments = $arguments;
        $this->uriBuilder = $uriBuilder;
    }

    /**
     * Get the request of the controller
     *
     * @return \TYPO3\Flow\Mvc\RequestInterface
     * @api
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the response of the controller
     *
     * @return \TYPO3\Flow\Mvc\ResponseInterface
     * @api
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get the arguments of the controller
     *
     * @return \TYPO3\Flow\Mvc\Controller\Arguments
     * @api
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Returns the URI Builder bound to this context
     *
     * @return \TYPO3\Flow\Mvc\Routing\UriBuilder
     * @api
     */
    public function getUriBuilder()
    {
        return $this->uriBuilder;
    }

    /**
     * Get the flash message container
     *
     * @return \TYPO3\Flow\Mvc\FlashMessageContainer A container for flash messages
     * @api
     */
    public function getFlashMessageContainer()
    {
        return $this->flashMessageContainer;
    }
}
