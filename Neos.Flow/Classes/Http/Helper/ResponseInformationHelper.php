<?php
declare(strict_types=1);

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

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Utils;
use Neos\Flow\Http\CacheControlDirectives;
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
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    public static function createFromRaw(string $rawResponse): ResponseInterface
    {
        return Message::parseResponse($rawResponse);
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
            103 => 'Early Hints', // RFC 8297

            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content', // RFC 7233
            207 => 'Multi-Status', // WebDAV; RFC 4918
            208 => 'Already Reported', // WebDAV; RFC 5842
            226 => 'IM Used', // RFC 3229

            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified', // RFC 7232
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect', // RFC 7538

            400 => 'Bad Request',
            401 => 'Unauthorized', // RFC 7235
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required', // RFC 7235
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed', // RFC 7232
            413 => 'Payload Too Large', // RFC 7231
            414 => 'URI Too Long', // RFC 7231
            415 => 'Unsupported Media Type', // RFC 7231
            416 => 'Range Not Satisfiable', // RFC 7233
            417 => 'Expectation Failed',
            418 => 'Sono Vibiemme', // 'I'm a teapot', RFC 2324, RFC 7168
            421 => 'Misdirected Request', // RFC 7540
            422 => 'Unprocessable Entity', // WebDAV; RFC 4918
            423 => 'Locked', // WebDAV; RFC 4918
            424 => 'Failed Dependency', // WebDAV; RFC 4918
            425 => 'Too Early', // RFC 8470
            426 => 'Upgrade Required',
            428 => 'Precondition Required', // RFC 6585
            429 => 'Too Many Requests', // RFC 6585
            431 => 'Request Header Fields Too Large', // RFC 6585
            444 => 'No Response', // nginx
            451 => 'Unavailable For Legal Reasons', // RFC 7725
            499 => 'Client Closed Request', // nginx

            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates', // RFC 2295
            507 => 'Insufficient Storage', // WebDAV; RFC 4918
            508 => 'Loop Detected', // WebDAV; RFC 5842
            509 => 'Bandwidth Limit Exceeded', // Apache Web Server/cPanel
            510 => 'Not Extended', // RFC 2774
            511 => 'Network Authentication Required', // RFC 6585
            599 => 'Network Connect Timeout Error',
        ];

        return $statusMessages[$statusCode] ?? 'Unknown Status';
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
        foreach ($response->getHeaders() as $name => $values) {
            if (strtolower($name) === 'set-cookie') {
                foreach ($values as $value) {
                    $preparedHeaders[] = $name . ': ' . $value;
                }
            } else {
                $preparedHeaders[] = $name . ': ' . implode(', ', $values);
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
        if ($statusCode === 200 && in_array($request->getMethod(), ['HEAD', 'GET'])) {
            if ($request->hasHeader('If-None-Match') && $response->hasHeader('ETag')) {
                $ifNoneMatchHeaders = $request->getHeader('If-None-Match');
                $eTagHeader = $response->getHeader('ETag')[0];
                foreach ($ifNoneMatchHeaders as $ifNoneMatchHeader) {
                    if (ltrim($ifNoneMatchHeader, 'W/') === ltrim($eTagHeader, 'W/')) {
                        $response = $response
                            ->withStatus(304);
                        break;
                    }
                }
            } elseif ($response->hasHeader('Last-Modified')) {
                if ($request->hasHeader('If-Modified-Since')) {
                    $ifModifiedSince = $request->getHeaderLine('If-Modified-Since');
                    $ifModifiedSinceDate = \DateTime::createFromFormat(DATE_RFC2822, $ifModifiedSince);
                    $lastModified = $response->getHeaderLine('Last-Modified');
                    $lastModifiedDate = \DateTime::createFromFormat(DATE_RFC2822, $lastModified);
                    if ($lastModifiedDate <= $ifModifiedSinceDate) {
                        $response = $response
                            ->withStatus(304);
                    }
                } elseif ($request->hasHeader('If-Unmodified-Since')) {
                    $ifUnmodifiedSince = $request->getHeaderLine('If-Unmodified-Since');
                    $ifUnmodifiedSinceDate = \DateTime::createFromFormat(DATE_RFC2822, $ifUnmodifiedSince);
                    $lastModified = $response->getHeaderLine('Last-Modified');
                    $lastModifiedDate = \DateTime::createFromFormat(DATE_RFC2822, $lastModified);
                    if ($lastModifiedDate > $ifUnmodifiedSinceDate) {
                        $response = $response->withStatus(412);
                    }
                }
            }
        }

        if (in_array($response->getStatusCode(), [100, 101, 204, 304])) {
            $response = $response->withBody(Utils::streamFor());
        }

        if ($response->hasHeader('Cache-Control')) {
            $cacheControlHeaderValue = $response->getHeaderLine('Cache-Control');
            $cacheControlDirectives = CacheControlDirectives::fromRawHeader($cacheControlHeaderValue);
            if ($cacheControlDirectives->getDirective('no-cache') !== null || $response->hasHeader('Expires')) {
                $cacheControlDirectives->removeDirective('max-age');
            }
            $cacheControlHeaderValue = $cacheControlDirectives->getCacheControlHeaderValue();
            if ($cacheControlHeaderValue === null) {
                $response = $response->withoutHeader('Cache-Control');
            } else {
                $response = $response->withHeader('Cache-Control', $cacheControlHeaderValue);
            }
        }

        if (!$response->hasHeader('Content-Length')) {
            $response = $response->withHeader('Content-Length', $response->getBody()->getSize());
        }

        if ($request->getMethod() === 'HEAD') {
            $response = $response->withBody(Utils::streamFor());
        }

        if ($response->hasHeader('Transfer-Encoding')) {
            $response = $response->withoutHeader('Content-Length');
        }

        return $response;
    }
}
