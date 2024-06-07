<?php

/*
 * (c) Contributors of the Neos Project - www.neos.io
 * Please see the LICENSE file which was distributed with this source code.
 */

declare(strict_types=1);

namespace Neos\Flow\Testing\RequestHandler;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Core\RequestHandlerInterface;

/**
 * A request handler which boots up Flow into a basic runtime level and then returns
 * without actually further handling anything.
 *
 * As this request handler will be the "active" request handler returned by
 * the bootstrap's getActiveRequestHandler() method.
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class RuntimeSequenceInvokingRequestHandler implements RequestHandlerInterface
{
    protected Bootstrap $bootstrap;

    /**
     * Constructor
     *
     * @param \Neos\Flow\Core\Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * This request handler can handle requests in Testing Context.
     *
     * @return boolean If the context is Testing, true otherwise false
     */
    public function canHandleRequest(): bool
    {
        return true;
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * As this request handler can only be used as a preselected request handler,
     * the priority for all other cases is 0.
     *
     * @return integer The priority of the request handler.
     */
    public function getPriority(): int
    {
        return 0;
    }

    /**
     * Handles a command line request
     *
     * @return void
     */
    public function handleRequest(): void
    {
        $sequence = $this->bootstrap->buildRuntimeSequence();
        $sequence->invoke($this->bootstrap);
    }
}
