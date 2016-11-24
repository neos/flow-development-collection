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
use Neos\Flow\Core\RequestHandlerInterface;
use Neos\Flow\Exception as FlowException;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Mvc\Dispatcher;

/**
 * A special request handler which handles "slave" command requests as used by
 * the interactive shell.
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class SlaveRequestHandler implements RequestHandlerInterface
{
    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * Constructor
     *
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
        return (PHP_SAPI === 'cli' && isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === '--start-slave');
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return integer The priority of the request handler.
     */
    public function getPriority()
    {
        return 200;
    }

    /**
     * Creates an event loop which takes orders from the parent process and executes
     * them in runtime mode.
     *
     * @return void
     */
    public function handleRequest()
    {
        $sequence = $this->bootstrap->buildRuntimeSequence();
        $sequence->invoke($this->bootstrap);

        $objectManager = $this->bootstrap->getObjectManager();
        $systemLogger = $objectManager->get(SystemLoggerInterface::class);

        $systemLogger->log('Running sub process loop.', LOG_DEBUG);
        echo "\nREADY\n";

        try {
            while (true) {
                $commandLine = trim(fgets(STDIN));
                $trimmedCommandLine = trim($commandLine);
                $systemLogger->log(sprintf('Received command "%s".', $trimmedCommandLine), LOG_INFO);
                if ($commandLine === "QUIT\n") {
                    break;
                }
                /** @var Request $request */
                $request = $objectManager->get(RequestBuilder::class)->build($trimmedCommandLine);
                $response = new Response();
                if ($this->bootstrap->isCompiletimeCommand($request->getCommand()->getCommandIdentifier())) {
                    echo "This command must be executed during compiletime.\n";
                } else {
                    $objectManager->get(Dispatcher::class)->dispatch($request, $response);
                    $response->send();

                    $this->emitDispatchedCommandLineSlaveRequest();
                }
                echo "\nREADY\n";
            }

            $systemLogger->log('Exiting sub process loop.', LOG_DEBUG);
            $this->bootstrap->shutdown(Bootstrap::RUNLEVEL_RUNTIME);
            exit($response->getExitCode());
        } catch (\Exception $exception) {
            $this->handleException($exception);
        }
    }

    /**
     * Emits a signal that a CLI slave request was dispatched.
     *
     * @return void
     * @Flow\Signal
     */
    protected function emitDispatchedCommandLineSlaveRequest()
    {
        $this->bootstrap->getSignalSlotDispatcher()->dispatch(__CLASS__, 'dispatchedCommandLineSlaveRequest', []);
    }

    /**
     * Displays a human readable, partly beautified version of the given exception
     * and stops the application, return a non-zero exit code.
     *
     * @param \Exception $exception
     * @return void
     */
    protected function handleException(\Exception $exception)
    {
        $response = new Response();

        $exceptionMessage = '';
        $exceptionReference = "\n<b>More Information</b>\n";
        $exceptionReference .= "  Exception code      #" . $exception->getCode() . "\n";
        $exceptionReference .= "  File                " . $exception->getFile() . ($exception->getLine() ? ' line ' . $exception->getLine() : '') . "\n";
        $exceptionReference .= ($exception instanceof FlowException ? "  Exception reference #" . $exception->getReferenceCode() . "\n" : '');
        foreach (explode(chr(10), wordwrap($exception->getMessage(), 73)) as $messageLine) {
            $exceptionMessage .= "  $messageLine\n";
        }

        $response->setContent(sprintf("<b>Uncaught Exception</b>\n%s%s\n", $exceptionMessage, $exceptionReference));
        $response->send();
        exit(1);
    }
}
