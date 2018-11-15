<?php
namespace Neos\Flow\Http;

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
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Http\Component\ComponentChain;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManagerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A request handler which can handle HTTP requests.
 *
 * @Flow\Scope("singleton")
 */
class RequestHandler implements HttpRequestHandlerInterface
{
    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var Component\ComponentChain
     */
    protected $baseComponentChain;

    /**
     * @var Component\ComponentContext
     */
    protected $componentContext;

    /**
     * Make exit() a closure so it can be manipulated during tests
     *
     * @var \Closure
     */
    public $exit;

    /**
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
        $this->exit = function () {
            exit();
        };
    }

    /**
     * This request handler can handle any web request.
     *
     * @return boolean If the request is a web request, true otherwise false
     * @api
     */
    public function canHandleRequest()
    {
        return (PHP_SAPI !== 'cli');
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return integer The priority of the request handler.
     * @api
     */
    public function getPriority()
    {
        return 100;
    }

    /**
     * Handles a HTTP request
     *
     * @return void
     */
    public function handleRequest()
    {
        // Create the request very early so the ResourceManagement has a chance to grab it:
        $request = Request::createFromEnvironment();
        $response = new Response();
        $this->componentContext = new ComponentContext($request, $response);

        $this->boot();
        $this->resolveDependencies();
        $response = $this->addPoweredByHeader($response);
        $this->componentContext->replaceHttpResponse($response);
        $baseUriSetting = $this->bootstrap->getObjectManager()->get(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.http.baseUri');
        if (!empty($baseUriSetting)) {
            $baseUri = new Uri($baseUriSetting);
            $request = $request->withAttribute(Request::ATTRIBUTE_BASE_URI, $baseUri);
            $this->componentContext->replaceHttpRequest($request);
        }

        $this->baseComponentChain->handle($this->componentContext);
        $response = $this->baseComponentChain->getResponse();

        $response->send();

        $this->bootstrap->shutdown(Bootstrap::RUNLEVEL_RUNTIME);
        $this->exit->__invoke();
    }

    /**
     * Returns the currently handled HTTP request
     *
     * @return Request
     * @api
     */
    public function getHttpRequest()
    {
        return $this->componentContext->getHttpRequest();
    }

    /**
     * Returns the HTTP response corresponding to the currently handled request
     *
     * @return Response
     * @api
     */
    public function getHttpResponse()
    {
        return $this->componentContext->getHttpResponse();
    }

    /**
     * Boots up Flow to runtime
     *
     * @return void
     */
    protected function boot()
    {
        $sequence = $this->bootstrap->buildRuntimeSequence();
        $sequence->invoke($this->bootstrap);
    }

    /**
     * Resolves a few dependencies of this request handler which can't be resolved
     * automatically due to the early stage of the boot process this request handler
     * is invoked at.
     *
     * @return void
     */
    protected function resolveDependencies()
    {
        $objectManager = $this->bootstrap->getObjectManager();
        $this->baseComponentChain = $objectManager->get(ComponentChain::class);
    }

    /**
     * Adds an HTTP header to the Response which indicates that the application is powered by Flow.
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws \Neos\Flow\Exception
     */
    protected function addPoweredByHeader(ResponseInterface $response): ResponseInterface
    {
        $token = static::prepareApplicationToken($this->bootstrap->getObjectManager());
        if ($token === '') {
            return $response;
        }

        return $response->withAddedHeader('X-Flow-Powered', $token);
    }

    /**
     * Renders a major version out of a full version string
     *
     * @param string $version For example "2.3.7"
     * @return string For example "2"
     */
    protected static function renderMajorVersion($version)
    {
        preg_match('/^(\d+)/', $version, $versionMatches);
        return isset($versionMatches[1]) ? $versionMatches[1] : '';
    }

    /**
     * Renders a minor version out of a full version string
     *
     * @param string $version For example "2.3.7"
     * @return string For example "2.3"
     */
    protected static function renderMinorVersion($version)
    {
        preg_match('/^(\d+\.\d+)/', $version, $versionMatches);
        return isset($versionMatches[1]) ? $versionMatches[1] : '';
    }

    /**
     * Generate an application information header for the response based on settings and package versions.
     * Will statically compile in production for performance benefits.
     *
     * @param ObjectManagerInterface $objectManager
     * @return string
     * @throws \Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException
     * @Flow\CompileStatic
     */
    public static function prepareApplicationToken(ObjectManagerInterface $objectManager): string
    {
        $configurationManager = $objectManager->get(ConfigurationManager::class);
        $tokenSetting = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.http.applicationToken');

        if (!in_array($tokenSetting, ['ApplicationName', 'MinorVersion', 'MajorVersion'])) {
            return '';
        }

        $applicationPackageKey = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.core.applicationPackageKey');
        $applicationName = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.core.applicationName');
        $applicationIsNotFlow = ($applicationPackageKey !== 'Neos.Flow');

        if ($tokenSetting === 'ApplicationName') {
            return 'Flow' . ($applicationIsNotFlow ? ' ' . $applicationName : '');
        }

        $packageManager = $objectManager->get(PackageManagerInterface::class);
        $flowPackage = $packageManager->getPackage('Neos.Flow');
        $applicationPackage = $applicationIsNotFlow ? $packageManager->getPackage($applicationPackageKey) : null;

        // At this point the $tokenSetting must be either "MinorVersion" or "MajorVersion" so lets use it.

        $versionRenderer = 'render' . $tokenSetting;
        $flowVersion = static::$versionRenderer($flowPackage->getInstalledVersion());
        $applicationVersion = $applicationIsNotFlow ? static::$versionRenderer($applicationPackage->getInstalledVersion()) : null;

        return 'Flow/' . ($flowVersion ?: 'dev') . ($applicationIsNotFlow ? ' ' . $applicationName . '/' . ($applicationVersion ?: 'dev') : '');
    }
}
