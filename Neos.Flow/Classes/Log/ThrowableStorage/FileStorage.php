<?php
namespace Neos\Flow\Log\ThrowableStorage;

use Neos\Flow\Log\PlainTextFormatter;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Utility\Files;

/**
 * Stores detailed information about throwables into files.
 */
class FileStorage implements ThrowableStorageInterface
{
    /**
     * @var string
     */
    protected $storagePath;

    /**
     * @var \Closure
     */
    protected $requestInformationRenderer;

    /**
     * @var \Closure
     */
    protected $backtraceRenderer;

    /**
     * FileStorage path.
     *
     * @param string $storagePath
     */
    public function injectStoragePath(string $storagePath)
    {
        $this->storagePath = $storagePath;
    }

    /**
     * @param \Closure $requestInformationRenderer
     * @return $this|ThrowableStorageInterface
     */
    public function setRequestInformationRenderer(\Closure $requestInformationRenderer)
    {
        $this->requestInformationRenderer = $requestInformationRenderer;
        return $this;
    }

    /**
     * @param \Closure $backtraceRenderer
     * @return $this|ThrowableStorageInterface
     */
    public function setBacktraceRenderer(\Closure $backtraceRenderer)
    {
        $this->backtraceRenderer = $backtraceRenderer;
        return $this;
    }

    /**
     * @param \Throwable $throwable
     * @param array $additionalData
     */
    public function logThrowable(\Throwable $throwable, array $additionalData = [])
    {
        $this->logError($throwable, $additionalData);
    }

    /**
     * Writes information about the given exception into the log.
     *
     * @param \Throwable $error \Exception or \Throwable
     * @param array $additionalData Additional data to log
     * @return void
     */
    protected function logError(\Throwable $error, array $additionalData = [])
    {
        $message = $this->getErrorLogMessage($error);

        if ($error->getPrevious() !== null) {
            $additionalData['previousException'] = $this->getErrorLogMessage($error->getPrevious());
        }

        if (!file_exists($this->storagePath)) {
            mkdir($this->storagePath);
        }
        if (!file_exists($this->storagePath) || !is_dir($this->storagePath) || !is_writable($this->storagePath)) {
            return 'Could not write exception backtrace into %s because the directory could not be created or is not writable.';
        }

        // FIXME: getReferenceCode should probably become an interface.
        $referenceCode = (is_callable([$error, 'getReferenceCode']) ? $error->getReferenceCode() : $this->generateUniqueReferenceCode());
        $errorDumpPathAndFilename = Files::concatenatePaths([$this->storagePath, $referenceCode . '.txt']);
        file_put_contents($errorDumpPathAndFilename, $this->renderErrorInfo($error, $additionalData));
        $message .= ' - See also: ' . basename($errorDumpPathAndFilename);

        return $message;
    }

    /**
     * Generates a reference code for this specific error event to make it findable.
     *
     * @return string
     */
    protected function generateUniqueReferenceCode()
    {
        return date('YmdHis', $_SERVER['REQUEST_TIME']) . substr(md5(rand()), 0, 6);
    }

    /**
     * Get current error post mortem informations with support for error chaining
     *
     * @param \Throwable $error
     * @param array $additionalData
     * @return string
     */
    protected function renderErrorInfo(\Throwable $error, array $additionalData = [])
    {
        $maximumDepth = 100;
        $backTrace = $error->getTrace();
        $message = $this->getErrorLogMessage($error);
        $postMortemInfo = $message . PHP_EOL . PHP_EOL . $this->renderBacktrace($backTrace);
        $depth = 0;
        while (($error->getPrevious() instanceof \Throwable) && $depth < $maximumDepth) {
            $error = $error->getPrevious();
            $message = 'Previous exception: ' . $this->getErrorLogMessage($error);
            $backTrace = $error->getTrace();
            $postMortemInfo .= PHP_EOL . $message . PHP_EOL . PHP_EOL . $this->renderBacktrace($backTrace);
            ++$depth;
        }

        if ($depth === $maximumDepth) {
            $postMortemInfo .= PHP_EOL . 'Maximum chainging depth reached ...';
        }

        $postMortemInfo .= PHP_EOL . $this->renderRequestInfo();
        $postMortemInfo .= PHP_EOL;
        $postMortemInfo .= (new PlainTextFormatter($additionalData))->format();

        return $postMortemInfo;
    }

    /**
     * @param \Throwable $error
     * @return string
     */
    protected function getErrorLogMessage(\Throwable $error)
    {
        $errorCodeNumber = ($error->getCode() > 0) ? ' #' . $error->getCode() : '';
        $backTrace = $error->getTrace();
        $line = isset($backTrace[0]['line']) ? ' in line ' . $backTrace[0]['line'] . ' of ' . $backTrace[0]['file'] : '';

        return 'Exception' . $errorCodeNumber . $line . ': ' . $error->getMessage();
    }

    /**
     * Renders background information about the circumstances of the exception.
     *
     * @param array $backtrace
     * @return string
     */
    protected function renderBacktrace($backtrace)
    {
        $output = '';
        if ($this->backtraceRenderer !== null) {
            $output = $this->backtraceRenderer->__invoke($backtrace);
        }

        return $output;
    }

    /**
     * Render information about the current request, if possible
     *
     * @return string
     */
    protected function renderRequestInfo()
    {
        $output = '';
        if ($this->requestInformationRenderer !== null) {
            $output = $this->requestInformationRenderer->__invoke();
        }

        return $output;
    }
}
