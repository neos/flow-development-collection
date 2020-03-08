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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A middleware that wraps an old Http Component and invokes it after continuing the middleware chain
 */
class ComponentAfterWrapperMiddleware implements MiddlewareInterface
{
    /**
     * @var ComponentInterface
     */
    protected $wrappedComponent;

    /**
     * @var ComponentContext
     */
    protected $componentContext;

    /**
     * @param ComponentInterface $wrappedComponent
     */
    public function __construct(ComponentInterface $wrappedComponent)
    {
        $this->wrappedComponent = $wrappedComponent;
    }

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
        if ($this->componentContext->getParameter(ComponentChain::class, 'cancel') === true) {
            $this->componentContext->setParameter(ComponentChain::class, 'cancel', null);
        } else {
            $response = $handler->handle($request);
            $this->componentContext->replaceHttpResponse($response);
        }
        $this->wrappedComponent->handle($this->componentContext);
        return $this->componentContext->getHttpResponse();
    }
}
