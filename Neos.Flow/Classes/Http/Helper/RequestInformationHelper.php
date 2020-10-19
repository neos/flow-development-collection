<?php
namespace Neos\Flow\Http\Helper;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Helper to extract various information from PSR-7 requests.
 */
abstract class RequestInformationHelper
{
    /**
     * Returns the relative path (i.e. relative to the web root) and name of the
     * script as it was accessed through the web server.
     *
     * @param ServerRequestInterface $request The request in question
     * @return string Relative path and name of the PHP script as accessed through the web
     * @api
     */
    public static function getScriptRequestPathAndFilename(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();
        if (isset($server['SCRIPT_NAME'])) {
            return $server['SCRIPT_NAME'];
        }
        if (isset($server['ORIG_SCRIPT_NAME'])) {
            return $server['ORIG_SCRIPT_NAME'];
        }

        return '';
    }

    /**
     * Returns the relative path (i.e. relative to the web root) to the script as
     * it was accessed through the web server.
     *
     * @param ServerRequestInterface $request The request in question
     * @return string Relative path to the PHP script as accessed through the web
     * @api
     */
    public static function getScriptRequestPath(ServerRequestInterface $request): string
    {
        // This is not a simple `dirname()` because on Windows it will end up with backslashes in the URL
        $requestPathSegments = explode('/', self::getScriptRequestPathAndFilename($request));
        array_pop($requestPathSegments);
        return implode('/', $requestPathSegments) . '/';
    }

    /**
     * Constructs a relative path for this request,
     * that is the path segments left after removing the baseUri.
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    public static function getRelativeRequestPath(ServerRequestInterface $request): string
    {
        $baseUri = self::generateBaseUri($request);
        return UriHelper::getRelativePath($baseUri, $request->getUri());
    }

    /**
     * Tries to detect the base URI of request.
     *
     * @param ServerRequestInterface $request
     * @return UriInterface
     */
    public static function generateBaseUri(ServerRequestInterface $request): UriInterface
    {
        $baseUri = clone $request->getUri();
        $baseUri = $baseUri->withQuery('');
        $baseUri = $baseUri->withFragment('');
        $baseUri = $baseUri->withPath(self::getScriptRequestPath($request));

        return $baseUri;
    }

    /**
     * Return the Request-Line of this Request Message, consisting of the method, the URI and the HTTP version
     * Would be, for example, "GET /foo?bar=baz HTTP/1.1"
     * Note that the URI part is, at the moment, only possible in the form "abs_path" since the
     * actual requestUri of the Request cannot be determined during the creation of the Request.
     *
     * @param RequestInterface $request
     * @return string
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec5.html#sec5.1
     */
    public static function generateRequestLine(RequestInterface $request): string
    {
        return sprintf("%s %s HTTP/%s\r\n", $request->getMethod(), $request->getRequestTarget(), $request->getProtocolVersion());
    }

    /**
     * Renders the HTTP headers - EXCLUDING the status header - of the given request
     *
     * @param RequestInterface $request
     * @return string
     */
    public static function renderRequestHeaders(RequestInterface $request): string
    {
        $renderedHeaders = '';
        $headers = $request->getHeaders();
        foreach (array_keys($headers) as $name) {
            $renderedHeaders .= $request->getHeaderLine($name);
        }

        return $renderedHeaders;
    }

    /**
     * Extract the charset from the content type header if available
     *
     * @param RequestInterface $request
     * @return string the found charset or empty string if none
     */
    public static function getContentCharset(RequestInterface $request): string
    {
        $contentType = $request->getHeaderLine('Content-Type');
        if (preg_match('/[^;]+; ?charset=(?P<charset>[^;]+);?.*/', $contentType, $matches)) {
            return $matches['charset'];
        }

        return '';
    }

    /**
     * Extract header key/value pairs from a $_SERVER array.
     *
     * @param array $server
     * @return array
     */
    public static function extractHeadersFromServerVariables(array $server): array
    {
        $headerFields = [];
        if (isset($server['PHP_AUTH_USER']) && isset($server['PHP_AUTH_PW'])) {
            $headerFields['Authorization'] = 'Basic ' . base64_encode($server['PHP_AUTH_USER'] . ':' . $server['PHP_AUTH_PW']);
        }

        foreach ($server as $name => $value) {
            if (strpos($name, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headerFields[$name] = $value;
            } elseif ($name == 'REDIRECT_REMOTE_AUTHORIZATION' && !isset($headerFields['Authorization'])) {
                $headerFields['Authorization'] = $value;
            } elseif (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))));
                $headerFields[$name] = $value;
            }
        }

        return $headerFields;
    }
}
