<?php
declare(strict_types=1);

namespace Neos\Flow\Http\Middleware;

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
use Neos\Flow\Http\Component\ComponentChain;
use Neos\Flow\Http\Component\ComponentContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A middleware that invokes the old Http component chain and returns the final response in the component context
 */
class ComponentChainMiddleware implements MiddlewareInterface
{
    /**
     * @Flow\Inject
     * @var ComponentChain
     */
    protected $componentChain;

    /**
     * @var ComponentContext
     */
    protected $componentContext;

    /**
     * @param ComponentContext $context
     */
    public function setComponentContext(ComponentContext $context)
    {
        $this->componentContext = $context;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->componentContext->replaceHttpRequest($request);
        $this->componentChain->handle($this->componentContext);
        return $this->componentContext->getHttpResponse();
    }
}
