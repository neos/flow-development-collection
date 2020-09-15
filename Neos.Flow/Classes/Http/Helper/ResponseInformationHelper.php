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

use Neos\Flow\Http\Cookie;
use Neos\Flow\Http\Headers;
use Neos\Flow\Http\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Helper to extract various information from PSR-7 responses.
 */
abstract class ResponseInformationHelper
{
    /**
     * Creates a response from the given raw, that is plain text, HTTP response.
     *
     * @param string $rawResponse
     *
     * @throws \InvalidArgumentException
     * @return ResponseInterface
     */
    public static function createFromRaw(string $rawResponse, string $responseClassName = Response::class): ResponseInterface
    {
        $response = new $responseClassName();

        if (!$response instanceof ResponseInterface) {
            throw new \InvalidArgumentException(sprintf('The given response class name "%s" does not implement the "%s" and cannot be created with this method.', $responseClassName, ResponseInterface::class));
        }

        // see https://tools.ietf.org/html/rfc7230#section-3.5
        $lines = explode(chr(10), $rawResponse);
        $statusLine = array_shift($lines);

        if (substr($statusLine, 0, 5) !== 'HTTP/') {
            throw new \InvalidArgumentException('The given raw HTTP message is not a valid response.', 1335175601);
        }
        list($httpAndVersion, $statusCode, $reasonPhrase) = explode(' ', $statusLine, 3);
        $version = explode('/', $httpAndVersion)[1];
        if (strlen($statusCode) !== 3) {
            // See https://tools.ietf.org/html/rfc7230#section-3.1.2
            throw new \InvalidArgumentException('The given raw HTTP message contains an invalid status code.', 1502981352);
        }
        $response = $response->withStatus((integer)$statusCode, trim($reasonPhrase));
        $response = $response->withProtocolVersion($version);

        $parsingHeader = true;
        $contentLines = [];
        $headers = new Headers();
        foreach ($lines as $line) {
            if ($parsingHeader) {
                if (trim($line) === '') {
                    $parsingHeader = false;
                    continue;
                }
                $headerSeparatorIndex = strpos($line, ':');
                if ($headerSeparatorIndex === false) {
                    throw new \InvalidArgumentException('The given raw HTTP message contains an invalid header.', 1502984804);
                }
                $fieldName = trim(substr($line, 0, $headerSeparatorIndex));
                $fieldValue = trim(substr($line, strlen($fieldName) + 1));
                if (strtoupper(substr($fieldName, 0, 10)) === 'SET-COOKIE') {
                    $cookie = Cookie::createFromRawSetCookieHeader($fieldValue);
                    if ($cookie !== null) {
                        $headers->setCookie($cookie);
                    }
                } else {
                    $headers->set($fieldName, $fieldValue, false);
                }
            } else {
                $contentLines[] = $line;
            }
        }
        if ($parsingHeader === true) {
            throw new \InvalidArgumentException('The given raw HTTP message contains no separating empty line between header and body.', 1502984823);
        }
        $content = implode(chr(10), $contentLines);

        $response->setHeaders($headers);
        $response = $response->withBody(ArgumentsHelper::createContentStreamFromString($content));

        return $response;
    }

    /**
     * Returns the human-readable message for the given status code.
     *
     * @param integer $statusCode
     * @return string
     */
    public static function getStatusMessageByCode($statusCode): string
    {
        $statusMessages = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing', // RFC 2518
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => 'Sono Vibiemme',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            507 => 'Insufficient Storage',
            509 => 'Bandwidth Limit Exceeded',
        ];

        return isset($statusMessages[$statusCode]) ? $statusMessages[$statusCode] : 'Unknown Status';
    }

    /**
     * Return the Request-Line of this Request Message, consisting of the method, the URI and the HTTP version
     * Would be, for example, "GET /foo?bar=baz HTTP/1.1"
     * Note that the URI part is, at the moment, only possible in the form "abs_path" since the
     * actual requestUri of the Request cannot be determined during the creation of the Request.
     *
     * @param ResponseInterface $response
     * @return string
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html#sec6.1
     */
    public static function generateStatusLine(ResponseInterface $response): string
    {
        return sprintf("HTTP/%s %s %s\r\n", $response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase());
    }

    /**
     * Prepare array of header lines for this response
     *
     * @param ResponseInterface $response
     * @return array
     */
    public static function prepareHeaders(ResponseInterface $response): array
    {
        $preparedHeaders = [];
        $statusHeader = rtrim(self::generateStatusLine($response), "\r\n");

        $preparedHeaders[] = $statusHeader;
        $headers = $response->getHeaders();
        if ($headers instanceof Headers) {
            $preparedHeaders = array_merge($preparedHeaders, $headers->getPreparedValues());
        } else {
            foreach (array_keys($headers) as $name) {
                $preparedHeaders[] = $response->getHeaderLine($name);
            }
        }

        return $preparedHeaders;
    }

    /**
     * Analyzes this response, considering the given request and makes additions
     * or removes certain headers in order to make the response compliant to
     * RFC 2616 and related standards.
     *
     * It is recommended to call this method before the response is sent and Flow
     * does so by default in its built-in HTTP request handler.
     *
     * @param ResponseInterface $response
     * @param RequestInterface $request The corresponding request
     * @return ResponseInterface
     * @api
     */
    public static function makeStandardsCompliant(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        $statusCode = $response->getStatusCode();
        if ($request->hasHeader('If-Modified-Since') && $response->hasHeader('Last-Modified') && $statusCode === 200) {
            $ifModifiedSince = $request->getHeader('If-Modified-Since');
            $ifModifiedSinceDate = is_array($ifModifiedSince) ? reset($ifModifiedSince) : $ifModifiedSince;
            $lastModified = $response->getHeader('Last-Modified');
            $lastModifiedDate = is_array($lastModified) ? reset($lastModified) : $lastModified;
            if ($lastModifiedDate <= $ifModifiedSinceDate) {
                $response = $response->withStatus(304);
            }
        } elseif ($request->hasHeader('If-Unmodified-Since') && $response->hasHeader('Last-Modified')
            && (($statusCode >= 200 && $$statusCode <= 299) || $statusCode === 412)) {
            $unmodifiedSince = $request->getHeader('If-Unmodified-Since');
            $unmodifiedSinceDate = is_array($unmodifiedSince) ? reset($unmodifiedSince) : $unmodifiedSince;
            $lastModified = $response->getHeader('Last-Modified');
            $lastModifiedDate = is_array($lastModified) ? reset($lastModified) : $lastModified;
            if ($lastModifiedDate > $unmodifiedSinceDate) {
                $response = $response->withStatus(412);
            }
        }

        if (in_array($response->getStatusCode(), [100, 101, 204, 304])) {
            $response = $response->withBody(ArgumentsHelper::createContentStreamFromString(''));
        }

        $cacheControlHeaderLine = $response->getHeaderLine('Cache-Control');

        if (!empty($cacheControlHeaderLine) && strpos('no-cache', $cacheControlHeaderLine) !== false || $response->hasHeader('Expires')) {
            $cacheControlHeaderValue = trim(substr($cacheControlHeaderLine, 14));
            $cacheControlHeaderValue = str_replace('max-age', '', $cacheControlHeaderValue);
            $cacheControlHeaderValue = trim($cacheControlHeaderValue, ' ,');
            $response = $response->withHeader('Cache-Control', $cacheControlHeaderValue);
        }

        if (!$response->hasHeader('Content-Length')) {
            $response = $response->withHeader('Content-Length', $response->getBody()->getSize());
        }

        if ($request->getMethod() === 'HEAD') {
            $response = $response->withBody(ArgumentsHelper::createContentStreamFromString(''));
        }

        if ($response->hasHeader('Transfer-Encoding')) {
            $response = $response->withoutHeader('Content-Length');
        }

        return $response;
    }
}
