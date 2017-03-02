<?php
namespace Neos\Flow\Http\Component;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;

/**
 * The component context
 *
 * An instance of this class will be passed to each component of the chain allowing them to read/write parameters to/from it.
 * Besides handling of the chain is interrupted as soon as the "cancelled" flag is set.
 *
 * The instance will be created before the bootstrap, so AOP/DI proxying is not possible.
 *
 * @api
 * @Flow\Proxy(false)
 */
class ComponentContext
{
    /**
     * The current HTTP request
     *
     * @var Request
     */
    protected $httpRequest;

    /**
     * The current HTTP response
     *
     * @var Response
     */
    protected $httpResponse;

    /**
     * Two-dimensional array storing an parameter dictionary (containing variables that can be read/written by all components)
     * The first dimension is the fully qualified Component name, the second dimension is the identifier for the parameter.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * @param Request $httpRequest
     * @param Response $httpResponse
     */
    public function __construct(Request $httpRequest, Response $httpResponse)
    {
        $this->httpRequest = $httpRequest;
        $this->httpResponse = $httpResponse;
    }

    /**
     * @return Request
     * @api
     */
    public function getHttpRequest()
    {
        return $this->httpRequest;
    }

    /**
     * @param Request $httpRequest
     * @return void
     * @api
     */
    public function replaceHttpRequest(Request $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }

    /**
     * @return Response
     * @api
     */
    public function getHttpResponse()
    {
        return $this->httpResponse;
    }

    /**
     * @param Response $httpResponse
     * @return void
     * @api
     */
    public function replaceHttpResponse(Response $httpResponse)
    {
        $this->httpResponse = $httpResponse;
    }

    /**
     * @param string $componentClassName
     * @param string $parameterName
     * @return mixed
     * @api
     */
    public function getParameter($componentClassName, $parameterName)
    {
        return isset($this->parameters[$componentClassName][$parameterName]) ? $this->parameters[$componentClassName][$parameterName] : null;
    }

    /**
     * @param string $componentClassName
     * @param string $parameterName
     * @param mixed $value
     * @api
     */
    public function setParameter($componentClassName, $parameterName, $value)
    {
        if (!isset($this->parameters[$componentClassName])) {
            $this->parameters[$componentClassName] = [];
        }
        $this->parameters[$componentClassName][$parameterName] = $value;
    }
}
