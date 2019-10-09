<?php
namespace Neos\Http\Factories;

use function GuzzleHttp\Psr7\parse_query;
use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 *
 */
class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * @var UriFactoryInterface
     */
    protected $uriFactory;

    /**
     * @var string
     */
    protected $defaultUserAgent = '';

    /**
     * @var string
     */
    protected $scriptPath = '';

    /**
     * @var string
     */
    protected $defaultHttpVersion = '1.1';

    /**
     * ServerRequestFactory constructor.
     *
     * @param UriFactoryInterface $uriFactory
     * @param string $defaultUserAgent
     * @param string $scriptPath
     * @param string $defaultHttpVersion
     */
    public function __construct(
        UriFactoryInterface $uriFactory,
        string $defaultUserAgent = 'Flow/' . FLOW_VERSION_BRANCH,
        string $scriptPath = 'index.php',
        string $defaultHttpVersion = '1.1'
    )
    {
        $this->uriFactory = $uriFactory;
        $this->defaultUserAgent = $defaultUserAgent;
        $this->scriptPath = $scriptPath;
        $this->defaultHttpVersion = $defaultHttpVersion;
    }

    /**
     * @inheritDoc
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        if (is_string($uri)) {
            $uri = $this->uriFactory->createUri($uri);
        }

        $uriPort = $uri->getPort();
        $isDefaultPort = $uri->getScheme() === 'https' ? ($uriPort === 443) : ($uriPort === 80);
        $scriptName = '/' . basename($this->scriptPath);

        $defaultServerEnvironment = [
            'HTTP_USER_AGENT' => $this->defaultUserAgent,
            'HTTP_HOST' => $uri->getHost() . ($isDefaultPort !== true && $uriPort !== null ? ':' . $uriPort : ''),
            'SERVER_NAME' => $uri->getHost(),
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_PORT' => $uri->getPort() ?: 80,
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_FILENAME' => $this->scriptPath,
            'SERVER_PROTOCOL' => 'HTTP/' . $this->defaultHttpVersion,
            'SCRIPT_NAME' =>  $scriptName,
            'PHP_SELF' => $scriptName,
            'REQUEST_TIME' => time()
        ];

        $serverParams = array_replace($defaultServerEnvironment, $serverParams);
        $headers = RequestInformationHelper::extractHeadersFromServerVariables($serverParams);


        $serverRequest = new ServerRequest($method, $uri, $headers, null, $this->defaultHttpVersion, $serverParams);
        if ($uri->getQuery()) {
            parse_str($uri->getQuery(), $queryParams);
            $serverRequest = $serverRequest->withQueryParams($queryParams);
        }

        return $serverRequest;
    }
}
