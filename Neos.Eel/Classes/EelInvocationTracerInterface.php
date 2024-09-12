<?php

namespace Neos\Eel;

/**
 * @internal experimental tracer for eel. Could be used for example to collect and log deprecations.
 */
interface EelInvocationTracerInterface
{
    public function recordPropertyAccess(object $object, string $propertyName): void;

    /**
     * @param array<int, mixed> $arguments
     */
    public function recordMethodCall(object $object, string $methodName, array $arguments): void;

    /**
     * @param array<int, mixed> $arguments
     */
    public function recordFunctionCall(callable $function, string $functionName, array $arguments): void;
}
