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
use Neos\Flow\Command\HelpCommandController;
use Neos\Flow\Mvc\Controller\Argument;
use Neos\Flow\Mvc\Controller\ControllerInterface;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Exception\CommandException;
use Neos\Flow\Mvc\Exception\InvalidArgumentTypeException;
use Neos\Flow\Mvc\Exception\NoSuchCommandException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\Exception\UnsupportedRequestTypeException;
use Neos\Flow\Mvc\RequestInterface;
use Neos\Flow\Mvc\ResponseInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * A controller which processes requests from the command line
 *
 * @Flow\Scope("singleton")
 */
class CommandController implements ControllerInterface
{
    /**
     * @var Request
     * @api
     */
    protected $request;

    /**
     * @var Response
     * @api
     */
    protected $response;

    /**
     * @var Arguments
     * @api
     */
    protected $arguments;

    /**
     * Name of the command method
     *
     * @var string
     * @api
     */
    protected $commandMethodName = '';

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var CommandManager
     */
    protected $commandManager;

    /**
     * @var ConsoleOutput
     * @api
     */
    protected $output;

    /**
     * Constructs the command controller
     */
    public function __construct()
    {
        $this->arguments = new Arguments([]);
        $this->output = new ConsoleOutput();
    }

    /**
     * @param CommandManager $commandManager
     * @return void
     */
    public function injectCommandManager(CommandManager $commandManager)
    {
        $this->commandManager = $commandManager;
    }

    /**
     * Injects the reflection service
     *
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Processes a command line request.
     *
     * @param RequestInterface $request The request object
     * @param ResponseInterface $response The response, modified by this handler
     * @return void
     * @throws UnsupportedRequestTypeException if the controller doesn't support the current request type
     * @api
     */
    public function processRequest(RequestInterface $request, ResponseInterface $response)
    {
        if (!$request instanceof Request) {
            throw new UnsupportedRequestTypeException(sprintf('%s only supports command line requests – requests of type "%s" given.', get_class($this), get_class($request)), 1300787096);
        }

        $this->request = $request;
        $this->request->setDispatched(true);
        $this->response = $response;

        $this->commandMethodName = $this->resolveCommandMethodName();
        $this->initializeCommandMethodArguments();
        $this->mapRequestArgumentsToControllerArguments();
        $this->callCommandMethod();
    }

    /**
     * Resolves and checks the current command method name
     *
     * Note: The resulting command method name might not have the correct case, which isn't a problem because PHP is case insensitive regarding method names.
     *
     * @return string Method name of the current command
     * @throws NoSuchCommandException
     */
    protected function resolveCommandMethodName()
    {
        $commandMethodName = $this->request->getControllerCommandName() . 'Command';
        if (!is_callable([$this, $commandMethodName])) {
            throw new NoSuchCommandException(sprintf('A command method "%s()" does not exist in controller "%s".', $commandMethodName, get_class($this)), 1300902143);
        }

        return $commandMethodName;
    }

    /**
     * Initializes the arguments array of this controller by creating an empty argument object for each of the
     * method arguments found in the designated command method.
     *
     * @return void
     * @throws InvalidArgumentTypeException
     */
    protected function initializeCommandMethodArguments()
    {
        $this->arguments->removeAll();
        $methodParameters = $this->commandManager->getCommandMethodParameters(get_class($this), $this->commandMethodName);

        foreach ($methodParameters as $parameterName => $parameterInfo) {
            $dataType = null;
            if (isset($parameterInfo['type'])) {
                $dataType = $parameterInfo['type'];
            } elseif ($parameterInfo['array']) {
                $dataType = 'array';
            }
            if ($dataType === null) {
                throw new InvalidArgumentTypeException(sprintf('The argument type for parameter $%s of method %s->%s() could not be detected.', $parameterName, get_class($this), $this->commandMethodName), 1306755296);
            }
            $defaultValue = (isset($parameterInfo['defaultValue']) ? $parameterInfo['defaultValue'] : null);
            $this->arguments->addNewArgument($parameterName, $dataType, ($parameterInfo['optional'] === false), $defaultValue);
        }
    }

    /**
     * Maps arguments delivered by the request object to the local controller arguments.
     *
     * @return void
     */
    protected function mapRequestArgumentsToControllerArguments()
    {
        /** @var Argument $argument */
        foreach ($this->arguments as $argument) {
            $argumentName = $argument->getName();

            if ($this->request->hasArgument($argumentName)) {
                $argument->setValue($this->request->getArgument($argumentName));
                continue;
            }
            if (!$argument->isRequired()) {
                continue;
            }
            $argumentValue = null;
            while ($argumentValue === null) {
                $argumentValue = $this->output->ask(sprintf('<comment>Please specify the required argument "%s":</comment> ', $argumentName));
            }

            if ($argumentValue === null) {
                $exception = new CommandException(sprintf('Required argument "%s" is not set.', $argumentName), 1306755520);
                $this->forward('error', HelpCommandController::class, ['exception' => $exception]);
            }
            $argument->setValue($argumentValue);
        }
    }

    /**
     * Forwards the request to another command and / or CommandController.
     *
     * Request is directly transferred to the other command / controller
     * without the need for a new request.
     *
     * @param string $commandName
     * @param string $controllerObjectName
     * @param array $arguments
     * @return void
     * @throws StopActionException
     */
    protected function forward($commandName, $controllerObjectName = null, array $arguments = [])
    {
        $this->request->setDispatched(false);
        $this->request->setControllerCommandName($commandName);
        if ($controllerObjectName !== null) {
            $this->request->setControllerObjectName($controllerObjectName);
        }
        $this->request->setArguments($arguments);

        $this->arguments->removeAll();
        throw new StopActionException();
    }

    /**
     * Calls the specified command method and passes the arguments.
     *
     * If the command returns a string, it is appended to the content in the
     * response object. If the command doesn't return anything and a valid
     * view exists, the view is rendered automatically.
     *
     * @return void
     */
    protected function callCommandMethod()
    {
        $preparedArguments = [];
        /** @var Argument $argument */
        foreach ($this->arguments as $argument) {
            $preparedArguments[] = $argument->getValue();
        }

        $command = new Command(get_class($this), $this->request->getControllerCommandName());
        if ($command->isDeprecated()) {
            $suggestedCommandMessage = '';

            $relatedCommandIdentifiers = $command->getRelatedCommandIdentifiers();
            if ($relatedCommandIdentifiers !== []) {
                $suggestedCommandMessage = sprintf(
                    ', use the following command%s instead: %s',
                    count($relatedCommandIdentifiers) > 1 ? 's' : '',
                    implode(', ', $relatedCommandIdentifiers)
                );
            }
            $this->outputLine('<b>Warning:</b> This command is <b>DEPRECATED</b>%s%s', [$suggestedCommandMessage, PHP_EOL]);
        }

        $commandResult = call_user_func_array([$this, $this->commandMethodName], $preparedArguments);

        if (is_string($commandResult) && strlen($commandResult) > 0) {
            $this->response->appendContent($commandResult);
        } elseif (is_object($commandResult) && method_exists($commandResult, '__toString')) {
            $this->response->appendContent((string)$commandResult);
        }
    }

    /**
     * Returns the CLI Flow command depending on the environment
     *
     * @return string
     */
    public function getFlowInvocationString()
    {
        if (DIRECTORY_SEPARATOR === '/' || (isset($_SERVER['MSYSTEM']) && $_SERVER['MSYSTEM'] === 'MINGW32')) {
            return './flow';
        } else {
            return 'flow.bat';
        }
    }

    /**
     * Outputs specified text to the console window
     * You can specify arguments that will be passed to the text via sprintf
     *
     * @see http://www.php.net/sprintf
     *
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     * @return void
     * @api
     */
    protected function output($text, array $arguments = [])
    {
        $this->output->output($text, $arguments);
    }

    /**
     * Outputs specified text to the console window and appends a line break
     *
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     * @return void
     * @see output()
     * @see outputLines()
     * @api
     */
    protected function outputLine($text = '', array $arguments = [])
    {
        $this->output->outputLine($text, $arguments);
    }

    /**
     * Formats the given text to fit into MAXIMUM_LINE_LENGTH and outputs it to the
     * console window
     *
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     * @param integer $leftPadding The number of spaces to use for indentation
     * @return void
     * @see outputLine()
     * @api
     */
    protected function outputFormatted($text = '', array $arguments = [], $leftPadding = 0)
    {
        $this->output->outputFormatted($text, $arguments, $leftPadding);
    }

    /**
     * Exits the CLI through the dispatcher and makes sure that Flow is properly shut down.
     *
     * If your command relies on functionality which is triggered through the Bootstrap
     * shutdown (such as the persistence framework), you must use quit() instead of exit().
     *
     * @param integer $exitCode Exit code to return on exit (see http://www.php.net/exit)
     * @throws StopActionException
     * @return void
     */
    protected function quit($exitCode = 0)
    {
        $this->response->setExitCode($exitCode);
        throw new StopActionException;
    }

    /**
     * Sends the response and exits the CLI without any further code execution
     * Should be used for commands that flush code caches.
     *
     * @param integer $exitCode Exit code to return on exit
     * @return void
     */
    protected function sendAndExit($exitCode = 0)
    {
        $this->response->send();
        exit($exitCode);
    }
}
