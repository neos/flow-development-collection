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

/**
 * An abstract exception handler
 *
 */
abstract class AbstractExceptionHandler implements ExceptionHandlerInterface {

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * Injects the system logger
	 *
	 * @param \TYPO3\Flow\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 */
	public function injectSystemLogger(\TYPO3\Flow\Log\SystemLoggerInterface $systemLogger) {
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
		if (is_object($this->systemLogger)) {
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
	 * @param array $renderingOptions Rendering options as defined in the settings
	 * @param integer $statusCode The HTTP status code
	 * @param string $referenceCode The generated reference code, if any
	 * @return \TYPO3\Fluid\View\StandaloneView
	 */
	protected function buildCustomFluidView(array $renderingOptions, $statusCode, $referenceCode) {
		$statusMessage = \TYPO3\Flow\Http\Response::getStatusMessageByCode($statusCode);

		$fluidView = new \TYPO3\Fluid\View\StandaloneView();
		$fluidView->setTemplatePathAndFilename($renderingOptions['fluidTemplate']);
		if (isset($renderingOptions['variables'])) {
			$fluidView->assignMultiple($renderingOptions['variables']);
		}
		$fluidView->assignMultiple(array(
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
		if (!isset($this->options['renderingGroups'])) {
			return;
		}
		foreach ($this->options['renderingGroups'] as $renderingGroupSettings) {
			if (isset($renderingGroupSettings['matchingExceptionClassNames'])) {
				foreach ($renderingGroupSettings['matchingExceptionClassNames'] as $exceptionClassName) {
					if ($exception instanceof $exceptionClassName) {
						return $renderingGroupSettings['options'];
					}
				}
			}
		}
		foreach ($this->options['renderingGroups'] as $renderingGroupSettings) {
			if ($exception instanceof \TYPO3\Flow\Exception && isset($renderingGroupSettings['matchingStatusCodes'])) {
				foreach ($renderingGroupSettings['matchingStatusCodes'] as $statusCode) {
					if ($statusCode === $exception->getStatusCode()) {
						return $renderingGroupSettings['options'];
					}
				}
			}
		}
	}

}
?>