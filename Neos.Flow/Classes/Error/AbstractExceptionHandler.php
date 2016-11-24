<?php
namespace Neos\Flow\Error;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Cli\Response as CliResponse;
use Neos\Flow\Exception as FlowException;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Log\ThrowableLoggerInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Utility\ObjectAccess;
use Neos\Utility\Arrays;

/**
 * An abstract exception handler
 */
abstract class AbstractExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @var array
     */
    protected $options = [];

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
    public function injectSystemLogger(SystemLoggerInterface $systemLogger)
    {
        $this->systemLogger = $systemLogger;
    }

    /**
     * Sets options of this exception handler.
     *
     * @param array $options Options for the exception handler
     * @return void
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        unset($this->options['className']);
    }

    /**
     * Constructs this exception handler - registers itself as the default exception handler.
     *
     */
    public function __construct()
    {
        set_exception_handler([$this, 'handleException']);
    }

    /**
     * Handles the given exception
     *
     * @param object $exception The exception object - can be \Exception, or some type of \Throwable in PHP 7
     * @return void
     */
    public function handleException($exception)
    {
        // Ignore if the error is suppressed by using the shut-up operator @
        if (error_reporting() === 0) {
            return;
        }

        $this->renderingOptions = $this->resolveCustomRenderingOptions($exception);

        if (is_object($this->systemLogger) && isset($this->renderingOptions['logException']) && $this->renderingOptions['logException']) {
            if ($exception instanceof \Throwable) {
                if ($this->systemLogger instanceof ThrowableLoggerInterface) {
                    $this->systemLogger->logThrowable($exception);
                } else {
                    // Convert \Throwable to \Exception for non-supporting logger implementations
                    $this->systemLogger->logException(new \Exception($exception->getMessage(), $exception->getCode()));
                }
            } elseif ($exception instanceof \Exception) {
                $this->systemLogger->logException($exception);
            }
        }

        switch (PHP_SAPI) {
            case 'cli':
                $this->echoExceptionCli($exception);
                break;
            default:
                $this->echoExceptionWeb($exception);
        }
    }

    /**
     * Echoes an exception for the web.
     *
     * @param object $exception \Exception or \Throwable
     * @return void
     */
    abstract protected function echoExceptionWeb($exception);


    /**
     * Prepares a Fluid view for rendering the custom error page.
     *
     * @param object $exception \Exception or \Throwable
     * @param array $renderingOptions Rendering options as defined in the settings
     * @return ViewInterface
     */
    protected function buildView($exception, array $renderingOptions)
    {
        $statusCode = 500;
        $referenceCode = null;
        if ($exception instanceof FlowException) {
            $statusCode = $exception->getStatusCode();
            $referenceCode = $exception->getReferenceCode();
        }
        $statusMessage = Response::getStatusMessageByCode($statusCode);

        $viewClassName = $renderingOptions['viewClassName'];
        /** @var ViewInterface $view */
        $view = $viewClassName::createWithOptions($renderingOptions['viewOptions']);
        $view = $this->applyLegacyViewOptions($view, $renderingOptions);

        $httpRequest = Request::createFromEnvironment();
        $request = new ActionRequest($httpRequest);
        $request->setControllerPackageKey('Neos.Flow');
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);
        $view->setControllerContext(new ControllerContext(
            $request,
            new Response(),
            new Arguments([]),
            $uriBuilder
        ));

        if (isset($renderingOptions['variables'])) {
            $view->assignMultiple($renderingOptions['variables']);
        }

        $view->assignMultiple([
            'exception' => $exception,
            'renderingOptions' => $renderingOptions,
            'statusCode' => $statusCode,
            'statusMessage' => $statusMessage,
            'referenceCode' => $referenceCode
        ]);

        return $view;
    }

    /**
     * Sets legacy "option" properties to the view for backwards compatibility.
     *
     * @param ViewInterface $view
     * @param array $renderingOptions
     * @return ViewInterface
     */
    protected function applyLegacyViewOptions(ViewInterface $view, array $renderingOptions)
    {
        if (isset($renderingOptions['templatePathAndFilename'])) {
            ObjectAccess::setProperty($view, 'templatePathAndFilename', $renderingOptions['templatePathAndFilename']);
        }
        if (isset($renderingOptions['layoutRootPath'])) {
            ObjectAccess::setProperty($view, 'layoutRootPath', $renderingOptions['layoutRootPath']);
        }
        if (isset($renderingOptions['partialRootPath'])) {
            ObjectAccess::setProperty($view, 'partialRootPath', $renderingOptions['partialRootPath']);
        }
        if (isset($renderingOptions['format'])) {
            ObjectAccess::setProperty($view, 'format', $renderingOptions['format']);
        }

        return $view;
    }

    /**
     * Checks if custom rendering rules apply to the given $exception and returns those.
     *
     * @param object $exception \Exception or \Throwable
     * @return array the custom rendering options, or NULL if no custom rendering is defined for this exception
     */
    protected function resolveCustomRenderingOptions($exception)
    {
        $renderingOptions = [];
        if (isset($this->options['defaultRenderingOptions'])) {
            $renderingOptions = $this->options['defaultRenderingOptions'];
        }
        $renderingGroup = $this->resolveRenderingGroup($exception);
        if ($renderingGroup !== null) {
            $renderingOptions = Arrays::arrayMergeRecursiveOverrule($renderingOptions, $this->options['renderingGroups'][$renderingGroup]['options']);
            $renderingOptions['renderingGroup'] = $renderingGroup;
        }
        return $renderingOptions;
    }

    /**
     * @param object $exception \Exception or \Throwable
     * @return string name of the resolved renderingGroup or NULL if no group could be resolved
     */
    protected function resolveRenderingGroup($exception)
    {
        if (!isset($this->options['renderingGroups'])) {
            return null;
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

    /**
     * Formats and echoes the exception and its previous exceptions (if any) for the command line
     *
     * @param object $exception \Exception or \Throwable
     * @return void
     */
    protected function echoExceptionCli($exception)
    {
        $response = new CliResponse();

        $exceptionMessage = $this->renderSingleExceptionCli($exception);
        while (($exception = $exception->getPrevious()) !== null) {
            $exceptionMessage .= PHP_EOL . '<u>Nested exception:</u>' . PHP_EOL;
            $exceptionMessage .= $this->renderSingleExceptionCli($exception);
        }

        $response->setContent($exceptionMessage);
        $response->send();
        exit(1);
    }

    /**
     * Renders a single exception including message, code and affected file
     *
     * @param object $exception \Exception or \Throwable
     * @return string
     */
    protected function renderSingleExceptionCli($exception)
    {
        $exceptionMessageParts = $this->splitExceptionMessage($exception->getMessage());
        $exceptionMessage = '<error><b>' . $exceptionMessageParts['subject'] . '</b></error>' . PHP_EOL;
        if ($exceptionMessageParts['body'] !== '') {
            $exceptionMessage .= wordwrap($exceptionMessageParts['body'], 73, PHP_EOL) . PHP_EOL;
        }

        $exceptionMessage .= PHP_EOL;
        $exceptionMessage .= $this->renderExceptionDetailCli('Type', get_class($exception));
        if ($exception->getCode()) {
            $exceptionMessage .= $this->renderExceptionDetailCli('Code', $exception->getCode());
        }
        $exceptionMessage .= $this->renderExceptionDetailCli('File', str_replace(FLOW_PATH_ROOT, '', $exception->getFile()));
        $exceptionMessage .= $this->renderExceptionDetailCli('Line', $exception->getLine());
        if ($exception instanceof FlowException) {
            $exceptionMessage .= PHP_EOL . 'Open <b>Data/Logs/Exceptions/' . $exception->getReferenceCode() . '.txt</b> for a full stack trace.' . PHP_EOL;
        }
        return $exceptionMessage;
    }

    /**
     * Renders the given $value word-wrapped and prefixed with $label
     *
     * @param string $label
     * @param string $value
     * @return string
     *
     */
    protected function renderExceptionDetailCli($label, $value)
    {
        $result = '  <b>' . $label . ': </b>';
        $result .= wordwrap($value, 75, PHP_EOL . str_repeat(' ', strlen($label) + 4), true);
        $result .= PHP_EOL;
        return $result;
    }

    /**
     * Splits the given string into subject and body according to following rules:
     * - If the string is empty or does not contain more than one sentence nor line breaks, the subject will be equal to the string and body will be an empty string
     * - Otherwise the subject is everything until the first line break or end of sentence, the body contains the rest
     *
     * @param string $exceptionMessage
     * @return array in the format array('subject' => '<subject>', 'body' => '<body>');
     */
    protected function splitExceptionMessage($exceptionMessage)
    {
        $body = '';
        $pattern = '/
			(?<=                # Begin positive lookbehind.
			  [.!?]\s           # Either an end of sentence punct,
			| \n                # or line break
			)
			(?<!                # Begin negative lookbehind.
			  i\.E\.\s          # Skip "i.E."
			)                   # End negative lookbehind.
			/ix';
        $sentences = preg_split($pattern, $exceptionMessage, 2, PREG_SPLIT_NO_EMPTY);
        if (!isset($sentences[1])) {
            $subject = $exceptionMessage;
        } else {
            $subject = trim($sentences[0]);
            $body = trim($sentences[1]);
        }
        return [
            'subject' => $subject,
            'body' => $body
        ];
    }
}
