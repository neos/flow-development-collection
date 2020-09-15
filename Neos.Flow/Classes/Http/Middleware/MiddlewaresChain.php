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
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewaresChain implements MiddlewareInterface, RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    protected $chain;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $name
     * @param MiddlewareInterface[] $middlewaresChain
     */
    public function __construct($name = 'default', array $middlewaresChain = [])
    {
        array_walk($middlewaresChain, static function ($middleware) use ($name) {
            if (!$middleware instanceof MiddlewareInterface) {
                throw new Exception(sprintf('Invalid element "%s" in middleware chain "%s".', is_object($middleware) ? get_class($middleware) : gettype($middleware), $name));
            }
        });
        $this->name = $name;
        $this->chain = $middlewaresChain;
    }

    /**
     * @param MiddlewareInterface $middleware
     */
    public function append(MiddlewareInterface $middleware)
    {
        array_push($this->chain, $middleware);
    }

    /**
     * @param MiddlewareInterface $middleware
     */
    public function prepend(MiddlewareInterface $middleware)
    {
        array_unshift($this->chain, $middleware);
    }

    /**
     * The PSR-15 middleware implementation method
     *
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (count($this->chain) === 0) {
            return $handler->handle($request);
        }

        $middleware = array_shift($this->chain);
        return $middleware->process($request, $handler);
    }

    /**
     * The PSR-15 request handler implementation method
     *
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (count($this->chain) === 0) {
            return new Response();
        }

        return $this->process($request, $this);
    }
}
