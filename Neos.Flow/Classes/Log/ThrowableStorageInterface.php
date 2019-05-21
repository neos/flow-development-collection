<?php
namespace Neos\Flow\Log;

/**
 * An interface for storages that can log full exceptions and their stacktraces.
 *
 * @api
 */
interface ThrowableStorageInterface
{
    /**
     *
     * A factory method to create an instance of the throwable storage.
     * Note that throwable storages must work without proxy so all dependencies need to be resolved manually or via options.
     *
     * @param array $options
     * @return ThrowableStorageInterface
     */
    public static function createWithOptions(array $options): ThrowableStorageInterface;


    /**
     * Writes information about the given exception into the log.
     *
     * @param \Throwable $throwable The throwable to log
     * @param array $additionalData Additional data to log
     * @return string Informational message about the stored throwable
     * @api
     */
    public function logThrowable(\Throwable $throwable, array $additionalData = []);

    /**
     * Set a closure that returns information about the current request to be stored with the exception.
     * Note this is not yet public API and bound to change.
     *
     * @param \Closure $requestInformationRenderer
     * @return self
     */
    public function setRequestInformationRenderer(\Closure $requestInformationRenderer);

    /**
     * Set a closure that takes a backtrace array and returns a representation useful for this storage.
     * Note this is not yet public API and bound to change.
     *
     * @param \Closure $backtraceRenderer
     * @return self
     */
    public function setBacktraceRenderer(\Closure $backtraceRenderer);
}
