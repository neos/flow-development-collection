<?php
namespace TYPO3\Flow\Cli;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Mvc\Controller\ControllerInterface;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Annotations as Flow;

/**
 * A controller which processes requests from the command line
 *
 * @Flow\Scope("singleton")
 */
class CommandController implements ControllerInterface {

	const MAXIMUM_LINE_LENGTH = 79;

	/**
	 * @var \TYPO3\Flow\Cli\Request
	 */
	protected $request;

	/**
	 * @var \TYPO3\Flow\Cli\Response
	 */
	protected $response;

	/**
	 * @var \TYPO3\Flow\Mvc\Controller\Arguments
	 */
	protected $arguments;

	/**
	 * Name of the command method
	 *
	 * @var string
	 */
	protected $commandMethodName = '';

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * Constructs the controller
	 *
	 */
	public function __construct() {
		$this->arguments = new Arguments(array());
	}

	/**
	 * Injects the reflection service
	 *
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Processes a command line request.
	 *
	 * @param \TYPO3\Flow\Mvc\RequestInterface $request The request object
	 * @param \TYPO3\Flow\Mvc\ResponseInterface $response The response, modified by this handler
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Exception\UnsupportedRequestTypeException if the controller doesn't support the current request type
	 * @api
	 */
	public function processRequest(\TYPO3\Flow\Mvc\RequestInterface $request, \TYPO3\Flow\Mvc\ResponseInterface $response) {
		if (!$request instanceof \TYPO3\Flow\Cli\Request) {
			throw new \TYPO3\Flow\Mvc\Exception\UnsupportedRequestTypeException(get_class($this) . ' only supports command line requests – requests of type "' . get_class($request) . '" given.', 1300787096);
		}

		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;

		$this->commandMethodName = $this->resolveCommandMethodName();
		$this->initializeCommandMethodArguments();
		$this->mapRequestArgumentsToControllerArguments();
		$this->callCommandMethod();
	}

	/**
	 * Resolves and checks the current command method name
	 *
	 * Note: The resulting command method name might not have the correct case, which isn't a problem because PHP is
	 *       case insensitive regarding method names.
	 *
	 * @return string Method name of the current command
	 * @throws \TYPO3\Flow\Mvc\Exception\NoSuchCommandException
	 */
	protected function resolveCommandMethodName() {
		$commandMethodName = $this->request->getControllerCommandName() . 'Command';
		if (!is_callable(array($this, $commandMethodName))) {
			throw new \TYPO3\Flow\Mvc\Exception\NoSuchCommandException('A command method "' . $commandMethodName . '()" does not exist in controller "' . get_class($this) . '".', 1300902143);
		}
		return $commandMethodName;
	}

	/**
	 * Initializes the arguments array of this controller by creating an empty argument object for each of the
	 * method arguments found in the designated command method.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidArgumentTypeException
	 */
	protected function initializeCommandMethodArguments() {
		$this->arguments->removeAll();
		$methodParameters = $this->reflectionService->getMethodParameters(get_class($this), $this->commandMethodName);

		foreach ($methodParameters as $parameterName => $parameterInfo) {
			$dataType = NULL;
			if (isset($parameterInfo['type'])) {
				$dataType = $parameterInfo['type'];
			} elseif ($parameterInfo['array']) {
				$dataType = 'array';
			}
			if ($dataType === NULL) {
				throw new \TYPO3\Flow\Mvc\Exception\InvalidArgumentTypeException('The argument type for parameter $' . $parameterName . ' of method ' . get_class($this) . '->' . $this->commandMethodName . '() could not be detected.', 1306755296);
			}
			$defaultValue = (isset($parameterInfo['defaultValue']) ? $parameterInfo['defaultValue'] : NULL);
			$this->arguments->addNewArgument($parameterName, $dataType, ($parameterInfo['optional'] === FALSE), $defaultValue);
		}
	}

	/**
	 * Maps arguments delivered by the request object to the local controller arguments.
	 *
	 * @return void
	 */
	protected function mapRequestArgumentsToControllerArguments() {
		foreach ($this->arguments as $argument) {
			$argumentName = $argument->getName();

			if ($this->request->hasArgument($argumentName)) {
				$argument->setValue($this->request->getArgument($argumentName));
			} elseif ($argument->isRequired()) {
				$exception = new \TYPO3\Flow\Mvc\Exception\CommandException('Required argument "' . $argumentName  . '" is not set.', 1306755520);
				$this->forward('error', 'TYPO3\Flow\Command\HelpCommandController', array('exception' => $exception));
			}
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
	 * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
	 */
	protected function forward($commandName, $controllerObjectName = NULL, array $arguments = array()) {
		$this->request->setDispatched(FALSE);
		$this->request->setControllerCommandName($commandName);
		if ($controllerObjectName !== NULL) {
			$this->request->setControllerObjectName($controllerObjectName);
		}
		$this->request->setArguments($arguments);

		$this->arguments->removeAll();
		throw new \TYPO3\Flow\Mvc\Exception\StopActionException();
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
	protected function callCommandMethod() {
		$preparedArguments = array();
		foreach ($this->arguments as $argument) {
			$preparedArguments[] = $argument->getValue();
		}

		$commandResult = call_user_func_array(array($this, $this->commandMethodName), $preparedArguments);

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
	public function getFlowInvocationString() {
		if (DIRECTORY_SEPARATOR === '/' || (isset($_SERVER['MSYSTEM']) && $_SERVER['MSYSTEM'] === 'MINGW32')) {
			return './flow';
		} else {
			return 'flow.bat';
		}
	}

	/**
	 * Outputs specified text to the console window
	 * You can specify arguments that will be passed to the text via sprintf
	 * @see http://www.php.net/sprintf
	 *
	 * @param string $text Text to output
	 * @param array $arguments Optional arguments to use for sprintf
	 * @return void
	 */
	protected function output($text, array $arguments = array()) {
		if ($arguments !== array()) {
			$text = vsprintf($text, $arguments);
		}
		$this->response->appendContent($text);
	}

	/**
	 * Outputs specified text to the console window and appends a line break
	 *
	 * @param string $text Text to output
	 * @param array $arguments Optional arguments to use for sprintf
	 * @return void
	 * @see output()
	 * @see outputLines()
	 */
	protected function outputLine($text = '', array $arguments = array()) {
		$this->output($text . PHP_EOL, $arguments);
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
	 */
	protected function outputFormatted($text = '', array $arguments = array(), $leftPadding = 0) {
		$lines = explode(PHP_EOL, $text);
		foreach ($lines as $line) {
			$formattedText = str_repeat(' ', $leftPadding) . wordwrap($line, self::MAXIMUM_LINE_LENGTH - $leftPadding, PHP_EOL . str_repeat(' ', $leftPadding), TRUE);
			$this->outputLine($formattedText, $arguments);
		}
	}

	/**
	 * Exits the CLI through the dispatcher
	 * An exit status code can be specified @see http://www.php.net/exit
	 *
	 * @param integer $exitCode Exit code to return on exit
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
	 */
	protected function quit($exitCode = 0) {
		$this->response->setExitCode($exitCode);
		throw new \TYPO3\Flow\Mvc\Exception\StopActionException();
	}

	/**
	 * Sends the response and exits the CLI without any further code execution
	 * Should be used for commands that flush code caches.
	 *
	 * @param integer $exitCode Exit code to return on exit
	 * @return void
	 */
	protected function sendAndExit($exitCode = 0) {
		$this->response->send();
		exit($exitCode);
	}

}
