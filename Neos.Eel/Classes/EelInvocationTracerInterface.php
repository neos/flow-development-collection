<?php

namespace Neos\Eel;

/**
 * @internal experimental tracer for eel. Could be used for example to collect and log deprecations.
 */
interface EelInvocationTracerInterface
{
    public function recordPropertyAccess(object $object, string $propertyName): void;

    public function recordMethodCall(object $object, string $methodName): void;
}
