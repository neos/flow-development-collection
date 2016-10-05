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
use TYPO3\Flow\Mvc\FlashMessageContainer;
use TYPO3\Flow\Mvc\RequestInterface;
use TYPO3\Flow\Mvc\ResponseInterface;
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
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Arguments
     */
    protected $arguments;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @Flow\Inject
     * @var FlashMessageContainer
     */
    protected $flashMessageContainer;

    /**
     * Constructs this context
     *
     * @param RequestInterface $request
     * @param Response $response
     * @param Arguments $arguments
     * @param UriBuilder $uriBuilder
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
     * @return RequestInterface
     * @api
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the response of the controller
     *
     * @return ResponseInterface
     * @api
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get the arguments of the controller
     *
     * @return Arguments
     * @api
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Returns the URI Builder bound to this context
     *
     * @return UriBuilder
     * @api
     */
    public function getUriBuilder()
    {
        return $this->uriBuilder;
    }

    /**
     * Get the flash message container
     *
     * @return FlashMessageContainer A container for flash messages
     * @api
     */
    public function getFlashMessageContainer()
    {
        return $this->flashMessageContainer;
    }
}
