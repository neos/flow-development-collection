<?php
namespace Neos\Flow\Http;

/**
 * Declares attributes Flow uses in PSR-7 ServerRequestInterface
 * to carry derived information about the request.
 */
final class ServerRequestAttributes
{
    /**
     * PSR-7 Attribute containing the resolved trusted client IP address as string
     */
    public const CLIENT_IP = 'clientIpAddress';

    /**
     * PSR-7 Attribute containing a boolean whether the request is from a trusted proxy
     */
    public const TRUSTED_PROXY = 'fromTrustedProxy';

    /**
     * PSR-7 Attribute containing routing results as array to be merged in any merged arguments.
     */
    public const ROUTING_RESULTS = 'routingResults';

    /**
     * PSR-7 Attribute containing routing parameters as array to be used in routing.
     */
    public const ROUTING_PARAMETERS = 'routingParameters';

    /**
     * PSR-7 Attribute containing the action request to be dispatched.
     * @internal Don't use this. The ActionRequest is supposed to only exist inside the MVC dispatch context.
     */
    public const ACTION_REQUEST = 'actionRequest';
}
