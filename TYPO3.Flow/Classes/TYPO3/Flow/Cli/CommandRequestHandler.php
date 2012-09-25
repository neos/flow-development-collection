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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Cli\Response;

/**
 * A request handler which can handle command line requests.
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class CommandRequestHandler implements \TYPO3\Flow\Core\RequestHandlerInterface {

	/**
	 * @var \TYPO3\Flow\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Mvc\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var \TYPO3\Flow\Cli\Request
	 */
	protected $request;

	/**
	 * @var \TYPO3\Flow\Cli\Response
	 */
	protected $response;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 */
	public function __construct(Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 * This request handler can handle CLI requests.
	 *
	 * @return boolean If the request is a CLI request, TRUE otherwise FALSE
	 */
	public function canHandleRequest() {
		return (PHP_SAPI === 'cli');
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return integer The priority of the request handler.
	 */
	public function getPriority() {
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
	public function handleRequest() {
		try {
			$runLevel = $this->bootstrap->isCompiletimeCommand(isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '') ? 'Compiletime' : 'Runtime';
			$this->boot($runLevel);
			$this->objectManager->get('TYPO3\Flow\Cli\CommandExceptionHandler');
		} catch (\Exception $exception) {
			\TYPO3\Flow\Cli\CommandExceptionHandler::writeResponseAndExit($exception);
		}

		$commandLine = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();
		$this->request = $this->objectManager->get('TYPO3\Flow\Cli\RequestBuilder')->build(array_slice($commandLine, 1));
		$this->response = new Response();

		$this->exitIfCompiletimeCommandWasNotCalledCorrectly($runLevel);

		$this->dispatcher->dispatch($this->request, $this->response);
		$this->response->send();

		$this->shutdown($runLevel);
	}

	/**
	 * Checks if compile time command was not recognized as such, then runlevel was
	 * booted but it turned out that in fact the command is a compile time command.
	 *
	 * This happens if the user doesn't specify the full command identifier.
	 *
	 * @param string $runlevel
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidCommandIdentifierException
	 */
	public function exitIfCompiletimeCommandWasNotCalledCorrectly($runlevel) {
		if ($runlevel === 'Runtime') {
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
	}

	/**
	 * Initializes the matching boot sequence depending on the type of the command
	 * (runtime or compiletime) and manually injects the necessary dependencies of
	 * this request handler.
	 *
	 * @param string $runlevel Either "Compiletime" or "Runtime"
	 * @return void
	 */
	protected function boot($runlevel) {
		$sequence = ($runlevel === 'Compiletime') ? $this->bootstrap->buildCompiletimeSequence() : $this->bootstrap->buildRuntimeSequence();
		$sequence->invoke($this->bootstrap);

		$this->objectManager = $this->bootstrap->getObjectManager();
		$this->dispatcher = $this->objectManager->get('TYPO3\Flow\Mvc\Dispatcher');
	}

	/**
	 * Starts the shutdown sequence
	 *
	 * @param string $runlevel Either "Compiletime" or "Runtime"
	 * @return void
	 */
	protected function shutdown($runlevel) {
		$this->bootstrap->shutdown($runlevel);
		if ($runlevel === 'Compiletime') {
			$this->objectManager->get('TYPO3\Flow\Core\LockManager')->unlockSite();
		}
		exit($this->response->getExitCode());
	}
}

?>