<?php
namespace Neos\Flow\Cli;

use Neos\Flow\Cli\Exception\StopCommandException;

/**
 * Transitional interface for command controllers.
 * @internal
 */
interface CommandControllerInterface
{
    /**
     * Processes a general request. The result can be returned by altering the given response.
     *
     * @param Request $request The request object
     * @param Response $response The response, modified by the controller
     * @return void
     * @throws StopCommandException
     */
    public function processRequest(Request $request, Response $response): void;
}
