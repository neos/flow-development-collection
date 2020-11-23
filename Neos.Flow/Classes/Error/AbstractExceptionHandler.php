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

use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\Response;
use Neos\Flow\Exception as FlowException;
use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Utility\ObjectAccess;
use Neos\Utility\Arrays;
use Psr\Log\LoggerInterface;

/**
 * An abstract exception handler
 */
abstract class AbstractExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ThrowableStorageInterface
     */
    protected $throwableStorage;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $renderingOptions;

    /**
     * @param LoggerInterface $logger
     * @return void
     * @Flow\Autowiring(enabled=false)
     */
    public function injectLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ThrowableStorageInterface $throwableStorage
     */
    public function injectThrowableStorage(ThrowableStorageInterface $throwableStorage)
    {
        $this->throwableStorage = $throwableStorage;
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
     * @param \Throwable $exception The exception object
     * @return void
     */
    public function handleException($exception)
    {
        // Ignore if the error is suppressed by using the shut-up operator @
        if (error_reporting() === 0) {
            return;
        }

        $this->renderingOptions = $this->resolveCustomRenderingOptions($exception);

        if ($this->throwableStorage instanceof ThrowableStorageInterface && isset($this->renderingOptions['logException']) && $this->renderingOptions['logException']) {
            $message = $this->throwableStorage->logThrowable($exception);
            $this->logger->critical($message);
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
     * @param \Throwable $exception
     * @return void
     */
    abstract protected function echoExceptionWeb($exception);


    /**
     * Prepares a view for rendering the custom error page.
     *
     * @param \Throwable $exception
     * @param array $renderingOptions Rendering options as defined in the settings
     * @return ViewInterface
     */
    protected function buildView(\Throwable $exception, array $renderingOptions): ViewInterface
    {
        $statusCode = ($exception instanceof WithHttpStatusInterface) ? $exception->getStatusCode() : 500;
        $referenceCode = ($exception instanceof WithReferenceCodeInterface) ? $exception->getReferenceCode() : null;

        $statusMessage = ResponseInformationHelper::getStatusMessageByCode($statusCode);
        $viewClassName = $renderingOptions['viewClassName'];
        /** @var ViewInterface $view */
        $view = $viewClassName::createWithOptions($renderingOptions['viewOptions']);
        $view = $this->applyLegacyViewOptions($view, $renderingOptions);

        $httpRequest = ServerRequest::fromGlobals();
        $request = ActionRequest::fromHttpRequest($httpRequest);
        $request->setControllerPackageKey('Neos.Flow');
        $uriBuilder = new UriBuilder();
        $uriBuilder->setRequest($request);
        $view->setControllerContext(new ControllerContext(
            $request,
            new ActionResponse(),
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
    protected function applyLegacyViewOptions(ViewInterface $view, array $renderingOptions): ViewInterface
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
     * @param \Throwable $exception
     * @return array the custom rendering options, or NULL if no custom rendering is defined for this exception
     */
    protected function resolveCustomRenderingOptions(\Throwable $exception): array
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
     * @param \Throwable $exception
     * @return string name of the resolved renderingGroup or NULL if no group could be resolved
     */
    protected function resolveRenderingGroup(\Throwable $exception)
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
        return null;
    }

    /**
     * Formats and echoes the exception and its previous exceptions (if any) for the command line
     *
     * @param \Throwable $exception
     * @return void
     */
    protected function echoExceptionCli(\Throwable $exception)
    {
        $response = new Response();

        $exceptionMessage = $this->renderSingleExceptionCli($exception);
        $exceptionMessage = $this->renderNestedExceptonsCli($exception, $exceptionMessage);

        if ($exception instanceof FlowException) {
            $exceptionMessage .= PHP_EOL . 'Open <b>Data/Logs/Exceptions/' . $exception->getReferenceCode() . '.txt</b> for a full stack trace.' . PHP_EOL;
        }

        $response->setContent($exceptionMessage);
        $response->send();
        exit(1);
    }

    /**
     * @param \Throwable $exception
     * @param string $exceptionMessage
     * @return string
     */
    protected function renderNestedExceptonsCli(\Throwable $exception, string &$exceptionMessage): string
    {
        if (!$exception->getPrevious()) {
            return $exceptionMessage;
        }

        while ($exception = $exception->getPrevious()) {
            $exceptionMessage .= PHP_EOL . '<u>Nested exception:</u>' . PHP_EOL;
            $exceptionMessage .= $this->renderSingleExceptionCli($exception);
        }

        return $exceptionMessage;
    }

    /**
     * Renders a single exception including message, code and affected file
     *
     * @param \Throwable $exception
     * @return string
     */
    protected function renderSingleExceptionCli(\Throwable $exception): string
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
    protected function renderExceptionDetailCli(string $label, string $value): string
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
    protected function splitExceptionMessage(string $exceptionMessage): array
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
