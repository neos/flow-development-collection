<?php
namespace Neos\Flow\Cli;

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
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Core\LockManager;
use Neos\Flow\Core\RequestHandlerInterface;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Context;

/**
 * A request handler which can handle command line requests.
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class CommandRequestHandler implements RequestHandlerInterface
{
    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
    * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * This request handler can handle CLI requests.
     *
     * @return boolean If the request is a CLI request, TRUE otherwise FALSE
     */
    public function canHandleRequest()
    {
        return (PHP_SAPI === 'cli');
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return integer The priority of the request handler.
     */
    public function getPriority()
    {
        return 100;
    }

    /**
     * Handles a command line request.
     *
     * While booting, the Object Manager is not yet available for retrieving the CommandExceptionHandler.
     * For this purpose, possible occurring exceptions at this stage are caught manually and treated the
     * same way the CommandExceptionHandler treats exceptions on itself anyways.
     *
     * @return void
     */
    public function handleRequest()
    {
        $runLevel = $this->bootstrap->isCompiletimeCommand(isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '') ? Bootstrap::RUNLEVEL_COMPILETIME : Bootstrap::RUNLEVEL_RUNTIME;
        $this->boot($runLevel);

        $commandLine = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];
        $this->request = $this->objectManager->get(RequestBuilder::class)->build(array_slice($commandLine, 1));
        $this->response = new Response();

        $this->exitIfCompiletimeCommandWasNotCalledCorrectly($runLevel);

        if ($runLevel === Bootstrap::RUNLEVEL_RUNTIME) {
            /** @var Context $securityContext */
            $securityContext = $this->objectManager->get(Context::class);
            $securityContext->withoutAuthorizationChecks(function () {
                $this->dispatcher->dispatch($this->request, $this->response);
            });
        } else {
            $this->dispatcher->dispatch($this->request, $this->response);
        }

        $this->response->send();

        $this->shutdown($runLevel);
    }

    /**
     * Checks if compile time command was not recognized as such, then runlevel was
     * booted but it turned out that in fact the command is a compile time command.
     *
     * This happens if the user doesn't specify the full command identifier.
     *
     * @param string $runlevel one of the Bootstrap::RUNLEVEL_* constants
     * @return void
     */
    public function exitIfCompiletimeCommandWasNotCalledCorrectly($runlevel)
    {
        if ($runlevel === Bootstrap::RUNLEVEL_COMPILETIME) {
            return;
        }
        $command = $this->request->getCommand();
        if ($this->bootstrap->isCompiletimeCommand($command->getCommandIdentifier())) {
            $this->response->appendContent(sprintf(
                "<b>Unrecognized Command</b>\n\n" .
                "Sorry, but he command \"%s\" must be specified by its full command\n" .
                "identifier because it is a compile time command which cannot be resolved\n" .
                "from an abbreviated command identifier.\n\n",
                $command->getCommandIdentifier())
            );
            $this->response->send();
            $this->shutdown($runlevel);
            exit(1);
        }
    }

    /**
     * Initializes the matching boot sequence depending on the type of the command
     * (RUNLEVEL_RUNTIME or RUNLEVEL_COMPILETIME) and manually injects the necessary dependencies of
     * this request handler.
     *
     * @param string $runlevel one of the Bootstrap::RUNLEVEL_* constants
     * @return void
     */
    protected function boot($runlevel)
    {
        $sequence = ($runlevel === Bootstrap::RUNLEVEL_COMPILETIME) ? $this->bootstrap->buildCompiletimeSequence() : $this->bootstrap->buildRuntimeSequence();
        $sequence->invoke($this->bootstrap);

        $this->objectManager = $this->bootstrap->getObjectManager();
        $this->dispatcher = $this->objectManager->get(Dispatcher::class);
    }

    /**
     * Starts the shutdown sequence
     *
     * @param string $runlevel one of the Bootstrap::RUNLEVEL_* constants
     * @return void
     */
    protected function shutdown($runlevel)
    {
        $this->bootstrap->shutdown($runlevel);
        if ($runlevel === Bootstrap::RUNLEVEL_COMPILETIME) {
            $this->objectManager->get(LockManager::class)->unlockSite();
        }
        exit($this->response->getExitCode());
    }
}
