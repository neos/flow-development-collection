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

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewaresChain implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $chain;

    /**
     * @var \Closure[]
     */
    private $stepCallbacks = [];

    public function __construct(array $middlewaresChain)
    {
        array_walk($middlewaresChain, static function ($middleware) {
            if (!$middleware instanceof MiddlewareInterface) {
                throw new Exception(sprintf('Invalid element "%s" in middleware chain. Must implement %s.', is_object($middleware) ? get_class($middleware) : gettype($middleware), MiddlewareInterface::class));
            }
        });
        $this->chain = $middlewaresChain;
    }

    /**
     * Register a callback that is invoked whenever a middleware component is about to be processed
     *
     * Usage:
     *
     * $middlewaresChain->onStep(function(ServerRequestInterface $request) {
     *   // $request contains the latest instance of the server request
     * });
     *
     * @param \Closure $callback
     */
    public function onStep(\Closure $callback): void
    {
        $this->stepCallbacks[] = $callback;
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
        foreach ($this->stepCallbacks as $callback) {
            $callback($request);
        }
        $middleware = array_shift($this->chain);
        return $middleware->process($request, $this);
    }
}
