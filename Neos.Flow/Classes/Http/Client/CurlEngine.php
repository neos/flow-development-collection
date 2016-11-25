<?php
namespace Neos\Flow\Http\Client;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http;

/**
 * A Request Engine which uses cURL in order to send requests to external
 * HTTP servers.
 */
class CurlEngine implements RequestEngineInterface
{
    /**
     * @var array
     */
    protected $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_FORBID_REUSE => true,
        CURLOPT_TIMEOUT => 30,
    ];

    /**
     * Sets an option to be used by cURL.
     *
     * @param integer $optionName One of the CURLOPT_* constants
     * @param mixed $value The value to set
     */
    public function setOption($optionName, $value)
    {
        $this->options[$optionName] = $value;
    }

    /**
     * Sends the given HTTP request
     *
     * @param Http\Request $request
     * @return Http\Response The response or FALSE
     * @api
     * @throws Http\Exception
     * @throws CurlEngineException
     */
    public function sendRequest(Http\Request $request)
    {
        if (!extension_loaded('curl')) {
            throw new Http\Exception('CurlEngine requires the PHP CURL extension to be installed and loaded.', 1346319808);
        }

        $requestUri = $request->getUri();
        $curlHandle = curl_init((string)$requestUri);

        curl_setopt_array($curlHandle, $this->options);

        // Send an empty Expect header in order to avoid chunked data transfer (which we can't handle yet).
        // If we don't set this, cURL will set "Expect: 100-continue" for requests larger than 1024 bytes.
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, ['Expect:']);

        // If the content is a stream resource, use cURL's INFILE feature to stream it
        $content = $request->getContent();
        if (is_resource($content)) {
            curl_setopt_array($curlHandle,
                [
                    CURLOPT_INFILE => $content,
                    CURLOPT_INFILESIZE => $request->getHeader('Content-Length'),
                ]
            );
        }

        switch ($request->getMethod()) {
            case 'GET':
                if ($request->getContent()) {
                    // workaround because else the request would implicitly fall into POST:
                    curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'GET');
                    if (!is_resource($content)) {
                        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $content);
                    }
                }
            break;
            case 'POST':
                curl_setopt($curlHandle, CURLOPT_POST, true);
                if (!is_resource($content)) {
                    $body = $content !== '' ? $content : http_build_query($request->getArguments());
                    curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $body);
                }
            break;
            case 'PUT':
                curl_setopt($curlHandle, CURLOPT_PUT, true);
                if (!is_resource($content) && $content !== '') {
                    $inFileHandler = fopen('php://temp', 'r+');
                    fwrite($inFileHandler, $request->getContent());
                    rewind($inFileHandler);
                    curl_setopt_array($curlHandle, [
                        CURLOPT_INFILE => $inFileHandler,
                        CURLOPT_INFILESIZE => strlen($request->getContent()),
                    ]);
                }
            break;
            default:
                if (!is_resource($content)) {
                    $body = $content !== '' ? $content : http_build_query($request->getArguments());
                    curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $body);
                }
                curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, $request->getMethod());
        }

        $preparedHeaders = [];
        foreach ($request->getHeaders()->getAll() as $fieldName => $values) {
            foreach ($values as $value) {
                $preparedHeaders[] = $fieldName . ': ' . $value;
            }
        }
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $preparedHeaders);

        // curl_setopt($curlHandle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP && CURLPROTO_HTTPS);
        // CURLOPT_UPLOAD

        if ($requestUri->getPort() !== null) {
            curl_setopt($curlHandle, CURLOPT_PORT, $requestUri->getPort());
        }

        // CURLOPT_COOKIE

        $curlResult = curl_exec($curlHandle);
        if ($curlResult === false) {
            throw new CurlEngineException(sprintf('cURL reported error code %s with message "%s". Last requested URL was "%s" (%s).', curl_errno($curlHandle), curl_error($curlHandle), curl_getinfo($curlHandle, CURLINFO_EFFECTIVE_URL), $request->getMethod()), 1338906040);
        } elseif (strlen($curlResult) === 0) {
            return false;
        }
        curl_close($curlHandle);

        $response = Http\Response::createFromRaw($curlResult);
        if ($response->getStatusCode() === 100) {
            $response = Http\Response::createFromRaw($response->getContent(), $response);
        }
        return $response;
    }
}
