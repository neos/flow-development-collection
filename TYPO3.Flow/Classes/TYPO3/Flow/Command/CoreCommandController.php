<?php
namespace TYPO3\Flow\Command;

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
use TYPO3\Flow\Cache\Backend\FreezableBackendInterface;

/**
 * Command controller for core commands
 *
 * NOTE: This command controller will run in compile time (as defined in the package bootstrap)
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class CoreCommandController extends \TYPO3\Flow\Cli\CommandController {

	/**
	 * @var \TYPO3\Flow\Cli\RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @var \TYPO3\Flow\Mvc\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var \TYPO3\Flow\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * @var \TYPO3\Flow\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var \TYPO3\Flow\Cache\CacheManager
	 */
	protected $cacheManager;

	/**
	 * @var \TYPO3\Flow\Object\Proxy\Compiler
	 */
	protected $proxyClassCompiler;

	/**
	 * @var \TYPO3\Flow\Aop\Builder\ProxyClassBuilder
	 */
	protected $aopProxyClassBuilder;

	/**
	 * @var \TYPO3\Flow\Object\DependencyInjection\ProxyClassBuilder
	 */
	protected $dependencyInjectionProxyClassBuilder;

	/**
	 * @param \TYPO3\Flow\Cli\RequestBuilder $requestBuilder
	 * @return void
	 */
	public function injectRequestBuilder(\TYPO3\Flow\Cli\RequestBuilder $requestBuilder) {
		$this->requestBuilder = $requestBuilder;
	}

	/**
	 * @param \TYPO3\Flow\Mvc\Dispatcher $dispatcher
	 * @return void
	 */
	public function injectDispatcher(\TYPO3\Flow\Mvc\Dispatcher $dispatcher) {
		$this->dispatcher = $dispatcher;
	}

	/**
	 * @param \TYPO3\Flow\SignalSlot\Dispatcher $signalSlotDispatcher
	 * @return void
	 */
	public function injectSignalSlotDispatcher(\TYPO3\Flow\SignalSlot\Dispatcher $signalSlotDispatcher) {
		$this->signalSlotDispatcher = $signalSlotDispatcher;
	}

	/**
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	public function injectBootstrap(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 * @param \TYPO3\Flow\Cache\CacheManager $cacheManager
	 * @return void
	 */
	public function injectCacheManager(\TYPO3\Flow\Cache\CacheManager $cacheManager) {
		$this->cacheManager = $cacheManager;
	}

	/**
	 * @param \TYPO3\Flow\Object\Proxy\Compiler $proxyClassCompiler
	 * @return void
	 */
	public function injectProxyClassCompiler(\TYPO3\Flow\Object\Proxy\Compiler $proxyClassCompiler) {
		$this->proxyClassCompiler = $proxyClassCompiler;
	}

	/**
	 * @param \TYPO3\Flow\Aop\Builder\ProxyClassBuilder $aopProxyClassBuilder
	 * @return void
	 */
	public function injectAopProxyClassBuilder(\TYPO3\Flow\Aop\Builder\ProxyClassBuilder $aopProxyClassBuilder) {
		$this->aopProxyClassBuilder = $aopProxyClassBuilder;
	}

	/**
	 * @param \TYPO3\Flow\Object\DependencyInjection\ProxyClassBuilder $dependencyInjectionProxyClassBuilder
	 * @return void
	 */
	public function injectDependencyInjectionProxyClassBuilder(\TYPO3\Flow\Object\DependencyInjection\ProxyClassBuilder $dependencyInjectionProxyClassBuilder) {
		$this->dependencyInjectionProxyClassBuilder = $dependencyInjectionProxyClassBuilder;
	}

	/**
	 * Explicitly compile proxy classes
	 *
	 * The compile command triggers the proxy class compilation.
	 * Although a compilation run is triggered automatically by Flow, there might
	 * be cases in a production context where a manual compile run is needed.
	 *
	 * @Flow\Internal
	 * @param boolean $force If set, classes will be compiled even though the cache says that everything is up to date.
	 * @return void
	 */
	public function compileCommand($force = FALSE) {
		$objectConfigurationCache = $this->cacheManager->getCache('Flow_Object_Configuration');
		if ($force === FALSE) {
			if ($objectConfigurationCache->has('allCompiledCodeUpToDate')) {
				return;
			}
		}

		$classesCache = $this->cacheManager->getCache('Flow_Object_Classes');
		$this->proxyClassCompiler->injectClassesCache($classesCache);

		$this->aopProxyClassBuilder->injectObjectConfigurationCache($objectConfigurationCache);
		$this->aopProxyClassBuilder->build();
		$this->dependencyInjectionProxyClassBuilder->build();

		$classCount = $this->proxyClassCompiler->compile();

		$objectConfigurationCache->set('allCompiledCodeUpToDate', TRUE);

		$classesCacheBackend = $classesCache->getBackend();
		if ($this->bootstrap->getContext()->isProduction() && $classesCacheBackend instanceof FreezableBackendInterface) {
			$classesCache->getBackend()->freeze();
		}

		$this->emitFinishedCompilationRun($classCount);
	}

	/**
	 * Adjust file permissions for CLI and web server access
	 *
	 * This command adjusts the file permissions of the whole Flow application to
	 * the given command line user and webserver user / group.
	 *
	 * @param string $commandlineUser User name of the command line user, for example "john"
	 * @param string $webserverUser User name of the webserver, for example "www-data"
	 * @param string $webserverGroup Group name of the webserver, for example "www-data"
	 * @return void
	 */
	public function setFilePermissionsCommand($commandlineUser, $webserverUser, $webserverGroup) {
		// This command will never be really called. It rather acts as a stub for rendering the
		// documentation for this command. In reality, the "flow" command line script will already
		// check if this command is supposed to be called and invoke the setfilepermissions script
		// directly.
	}

	/**
	 * Migrate source files as needed
	 *
	 * This will apply pending code migrations defined in packages to all
	 * packages that do not yet have those migration applied.
	 *
	 * For every migration that has been run, it will create a commit in
	 * the package. This allows for easy inspection, rollback and use of
	 * the fixed code.
	 *
	 * @param boolean $status Show the migration status, do not run migrations
	 * @param string $packagesPath If set, use the given path as base when looking for packages
	 * @param string $packageKey If set, migrate only the given package
	 * @return void
	 * @see typo3.flow:doctrine:migrate
	 */
	public function migrateCommand($status = FALSE, $packagesPath = NULL, $packageKey = NULL) {
		// This command will never be really called. It rather acts as a stub for rendering the
		// documentation for this command. In reality, the "flow" command line script will already
		// check if this command is supposed to be called and invoke the migrate script
		// directly.
	}

	/**
	 * Run the interactive Shell
	 *
	 * The shell command runs Flow's interactive shell. This shell allows for
	 * entering commands like through the regular command line interface but
	 * additionally supports autocompletion and a user-based command history.
	 *
	 * @return void
	 */
	public function shellCommand() {
		if (!function_exists('readline_read_history')) {
			$this->outputLine('Interactive Shell is not available on this system!');
			$this->quit(1);
		}
		$subProcess = FALSE;
		$pipes = array();

		$historyPathAndFilename = getenv('HOME') . '/.flow_' . md5(FLOW_PATH_ROOT);
		readline_read_history($historyPathAndFilename);
		readline_completion_function(array($this, 'autocomplete'));

		echo "Flow Interactive Shell\n\n";

		while (true) {
			$commandLine = readline('Flow > ');
			if ($commandLine == '') {
				echo "\n";
				break;
			}

			readline_add_history($commandLine);
			readline_write_history($historyPathAndFilename);

			$request = $this->requestBuilder->build($commandLine);
			$response = new \TYPO3\Flow\Cli\Response();
			$command = $request->getCommand();

			if ($request === FALSE || $command->getCommandIdentifier() === FALSE) {
				echo "Bad command\n";
				continue;
			}
			if ($this->bootstrap->isCompiletimeCommand($command->getCommandIdentifier())) {
				$this->dispatcher->dispatch($request, $response);
				$response->send();
				if (is_resource($subProcess)) {
					$this->quitSubProcess($subProcess, $pipes);
				}
			} else {
				if (is_resource($subProcess)) {
					$subProcessStatus = proc_get_status($subProcess);
					if ($subProcessStatus['running'] === FALSE) {
						proc_close($subProcess);
					}
				};
				if (!is_resource($subProcess)) {
					list($subProcess, $pipes) = $this->launchSubProcess();
					if ($subProcess === FALSE || !is_array($pipes)) {
						echo "Failed launching the shell sub process for executing the runtime command.\n";
						continue;
					}
					$this->echoSubProcessResponse($pipes);
				}

				fwrite($pipes[0], "$commandLine\n");
				fflush($pipes[0]);
				$this->echoSubProcessResponse($pipes);

				if ($command->isFlushingCaches()) {
					$this->quitSubProcess($subProcess, $pipes);
				}
			}
		}

		if (is_resource($subProcess)) {
			$this->quitSubProcess($subProcess, $pipes);
		}

		echo "Bye!\n";
	}

	/**
	 * Signals that the compile command was successfully finished.
	 *
	 * @param integer $classCount Number of compiled proxy classes
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitFinishedCompilationRun($classCount) {
		$this->signalSlotDispatcher->dispatch(__CLASS__, 'finishedCompilationRun', array($classCount));
	}

	/**
	 * Launch sub process
	 *
	 * @return array The new sub process and its STDIN, STDOUT, STDERR pipes – or FALSE if an error occurred.
	 * @throws \RuntimeException
	 */
	protected function launchSubProcess() {
		$systemCommand = 'FLOW_ROOTPATH=' . FLOW_PATH_ROOT . ' ' . 'FLOW_CONTEXT=' . $this->bootstrap->getContext() . ' ' . PHP_BINDIR . '/php -c ' . php_ini_loaded_file() . ' ' . FLOW_PATH_FLOW . 'Scripts/flow.php' . ' --start-slave';
		$descriptorSpecification = array(array('pipe', 'r'), array('pipe', 'w'), array('pipe', 'a'));
		$subProcess = proc_open($systemCommand, $descriptorSpecification, $pipes);
		if (!is_resource($subProcess)) {
			throw new \RuntimeException('Could not execute sub process.');
		}

		$read = array($pipes[1]);
		$write = null;
		$except = null;
		$readTimeout = 30;

		stream_select($read, $write, $except, $readTimeout);

		$subProcessStatus = proc_get_status($subProcess);
		return ($subProcessStatus['running'] === TRUE) ? array($subProcess, $pipes) : FALSE;
	}

	/**
	 * Echoes the currently pending response from the sub process
	 *
	 * @param array $pipes
	 * @return void
	 */
	protected function echoSubProcessResponse(array $pipes) {
		while (feof($pipes[1]) === FALSE) {
			$responseLine = fgets($pipes[1]);
			if (trim($responseLine) === 'READY' || $responseLine === FALSE) {
				break;
			}
			echo($responseLine);
		}
	}

	/**
	 * Cleanly terminates the given sub process
	 *
	 * @param resource $subProcess The sub process to quite
	 * @param array $pipes The current STDIN, STDOUT and STDERR pipes
	 * @return void
	 */
	protected function quitSubProcess($subProcess, array $pipes) {
		fwrite($pipes[0], "QUIT\n");
		fclose($pipes[0]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		proc_close($subProcess);
	}

	/**
	 * Returns autocomplete suggestions on hitting the TAB key.
	 *
	 * @param string $partialCommand The current (partial) command where the TAB key was hit
	 * @param integer $index The cursor index at the current (partial) command
	 * @return array
	 */
	protected function autocomplete($partialCommand, $index) {
		// @TODO Add more functionality by parsing the current buffer with readline_info()
		// @TODO Filter file system elements (if possible at all)

		$suggestions = array();

		$availableCommands = $this->bootstrap->getObjectManager()
			->get('TYPO3\Flow\Cli\CommandManager')
			->getAvailableCommands();

		/** @var $command \TYPO3\Flow\Cli\Command */
		foreach ($availableCommands as $command) {
			if ($command->isInternal() === FALSE) {
				$suggestions[] = $command->getCommandIdentifier();
			}
		}

		return $suggestions;
	}
}
?>