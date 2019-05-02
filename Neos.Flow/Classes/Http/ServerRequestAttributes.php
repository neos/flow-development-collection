<?php
namespace Neos\Flow\Http;

/**
 *
 */
final class ServerRequestAttributes
{
    /**
     * PSR-7 Attribute containing the resolved trusted client IP address as string
     */
    const ATTRIBUTE_CLIENT_IP = 'clientIpAddress';

    /**
     * PSR-7 Attribute containing a boolean whether the request is from a trusted proxy
     */
    const ATTRIBUTE_TRUSTED_PROXY = 'fromTrustedProxy';

    /**
     * PSR-7 Attribute containing the base URI for this request.
     */
    const ATTRIBUTE_BASE_URI = 'baseUri';

    /**
     *
     */
    const ATTRIBUTE_ROUTING_RESULTS = 'routingResults';
}
