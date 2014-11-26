<?php
namespace TYPO3\Flow\Error;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Exception as FlowException;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Fluid\View\StandaloneView;

require_once('Exception.php');

/**
 * An abstract exception handler
 */
abstract class AbstractExceptionHandler implements ExceptionHandlerInterface {

	/**
	 * @var SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var array
	 */
	protected $renderingOptions;

	/**
	 * Injects the system logger
	 *
	 * @param SystemLoggerInterface $systemLogger
	 * @return void
	 */
	public function injectSystemLogger(SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Sets options of this exception handler.
	 *
	 * @param array $options Options for the exception handler
	 * @return void
	 */
	public function setOptions(array $options) {
		$this->options = $options;
		unset($this->options['className']);
	}

	/**
	 * Constructs this exception handler - registers itself as the default exception handler.
	 *
	 */
	public function __construct() {
		set_exception_handler(array($this, 'handleException'));
	}

	/**
	 * Handles the given exception
	 *
	 * @param \Exception $exception The exception object
	 * @return void
	 */
	public function handleException(\Exception $exception) {
		// Ignore if the error is suppressed by using the shut-up operator @
		if (error_reporting() === 0) {
			return;
		}

		$this->renderingOptions = $this->resolveCustomRenderingOptions($exception);

		if (is_object($this->systemLogger) && isset($this->renderingOptions['logException']) && $this->renderingOptions['logException']) {
			$this->systemLogger->logException($exception);
		}

		switch (PHP_SAPI) {
			case 'cli' :
				$this->echoExceptionCli($exception);
				break;
			default :
				$this->echoExceptionWeb($exception);
		}
	}

	/**
	 * Echoes an exception for the command line.
	 *
	 * @param \Exception $exception The exception
	 * @return void
	 */
	abstract protected function echoExceptionCli(\Exception $exception);

	/**
	 * Echoes an exception for the web.
	 *
	 * @param \Exception $exception The exception
	 * @return void
	 */
	abstract protected function echoExceptionWeb(\Exception $exception);


	/**
	 * Prepares a Fluid view for rendering the custom error page.
	 *
	 * @param \Exception $exception
	 * @param array $renderingOptions Rendering options as defined in the settings
	 * @return StandaloneView
	 */
	protected function buildCustomFluidView(\Exception $exception, array $renderingOptions) {
		$statusCode = 500;
		$referenceCode = NULL;
		if ($exception instanceof FlowException) {
			$statusCode = $exception->getStatusCode();
			$referenceCode = $exception->getReferenceCode();
		}
		$statusMessage = Response::getStatusMessageByCode($statusCode);

		$fluidView = new StandaloneView();
		$fluidView->getRequest()->setControllerPackageKey('TYPO3.Flow');
		$fluidView->setTemplatePathAndFilename($renderingOptions['templatePathAndFilename']);
		if (isset($renderingOptions['layoutRootPath'])) {
			$fluidView->setLayoutRootPath($renderingOptions['layoutRootPath']);
		}
		if (isset($renderingOptions['partialRootPath'])) {
			$fluidView->setPartialRootPath($renderingOptions['partialRootPath']);
		}
		if (isset($renderingOptions['format'])) {
			$fluidView->setFormat($renderingOptions['format']);
		}
		if (isset($renderingOptions['variables'])) {
			$fluidView->assignMultiple($renderingOptions['variables']);
		}
		$fluidView->assignMultiple(array(
			'exception' => $exception,
			'renderingOptions' => $renderingOptions,
			'statusCode' => $statusCode,
			'statusMessage' => $statusMessage,
			'referenceCode' => $referenceCode
		));
		return $fluidView;
	}

	/**
	 * Checks if custom rendering rules apply to the given $exception and returns those.
	 *
	 * @param \Exception $exception
	 * @return array the custom rendering options, or NULL if no custom rendering is defined for this exception
	 */
	protected function resolveCustomRenderingOptions(\Exception $exception) {
		$renderingOptions = array();
		if (isset($this->options['defaultRenderingOptions'])) {
			$renderingOptions = $this->options['defaultRenderingOptions'];
		}
		$renderingGroup = $this->resolveRenderingGroup($exception);
		if ($renderingGroup !== NULL) {
			$renderingOptions = Arrays::arrayMergeRecursiveOverrule($renderingOptions, $this->options['renderingGroups'][$renderingGroup]['options']);
			$renderingOptions['renderingGroup'] = $renderingGroup;
		}
		return $renderingOptions;
	}

	/**
	 * @param \Exception $exception
	 * @return string name of the resolved renderingGroup or NULL if no group could be resolved
	 */
	protected function resolveRenderingGroup(\Exception $exception) {
		if (!isset($this->options['renderingGroups'])) {
			return NULL;
		}
		foreach ($this->options['renderingGroups'] as $renderingGroupName => $renderingGroupSettings) {
			if (isset($renderingGroupSettings['matchingExceptionClassNames'])) {
				foreach ($renderingGroupSettings['matchingExceptionClassNames'] as $exceptionClassName) {
					if ($exception instanceof $exceptionClassName) {
						return $renderingGroupName;
					}
				}
			}
			if (isset($renderingGroupSettings['matchingStatusCodes']) && $exception instanceof FlowException) {
				if (in_array($exception->getStatusCode(), $renderingGroupSettings['matchingStatusCodes'])) {
					return $renderingGroupName;
				}
			}
		}
	}

}
