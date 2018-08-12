<?php
namespace Neos\Flow\Http\Helper;

use Neos\Flow\Http\Headers;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Helper to extract various informations from PSR-7 requests.
 */
abstract class RequestInformationHelper
{
    /**
     * Returns the relative path (ie. relative to the web root) and name of the
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
     * Returns the relative path (ie. relative to the web root) to the script as
     * it was accessed through the web server.
     *
     * @param ServerRequestInterface $request The request in question
     * @return string Relative path to the PHP script as accessed through the web
     * @api
     */
    public static function getScriptRequestPath(ServerRequestInterface $request)
    {
        // FIXME: Shouldn't this be a simple dirname on getScriptRequestPathAndFilename
        $requestPathSegments = explode('/', self::getScriptRequestPathAndFilename($request));
        array_pop($requestPathSegments);
        return implode('/', $requestPathSegments) . '/';
    }

    /**
     * Returns the request's path relative to the $baseUri
     *
     * @param UriInterface $baseUri The base URI to start from
     * @param UriInterface $uri The URI in quesiton
     * @return string
     */
    public static function getRelativePath(UriInterface $baseUri, UriInterface $uri): string
    {
        // FIXME: We should probably do a strpos === 0 instead to make sure
        // the baseUri actually matches the start of the Uri.
        $baseUriLength = strlen($baseUri->getPath());
        if ($baseUriLength >= strlen($uri->getPath())) {
            return '';
        }

        return substr($uri->getPath(), $baseUriLength);
    }

    /**
     * Tries to detect the base URI of request.
     *
     * @param ServerRequestInterface $request
     * @return UriInterface
     */
    public static function generateBaseUri(ServerRequestInterface $request)
    {
        $baseUri = clone $request->getUri();
        $baseUri = $baseUri->withQuery('');
        $baseUri = $baseUri->withFragment('');
        $baseUri = $baseUri->withPath(self::getScriptRequestPath($request));

        return $baseUri;
    }

    /**
     * Return the Request-Line of this Request Message, consisting of the method, the uri and the HTTP version
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
        $uri = $request->getUri();
        $requestUri = $uri->getPath() .
            ($uri->getQuery() ? '?' . $uri->getQuery() : '') .
            ($uri->getFragment() ? '#' . $uri->getFragment() : '');

        return sprintf("%s %s HTTP/%s\r\n", $request->getMethod(), $requestUri, $request->getProtocolVersion());
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
        if ($headers instanceof Headers) {
            $renderedHeaders .= $headers->__toString();
        } else {
            foreach ($headers as $name => $values) {
                $renderedHeaders .= $name . ": " . implode(", ", $values) . "\r\n";
            }
        }

        return $renderedHeaders;
    }
}
