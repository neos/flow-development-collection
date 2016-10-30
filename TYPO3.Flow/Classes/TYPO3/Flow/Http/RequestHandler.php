<?php
namespace TYPO3\Flow\Http;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Http\Component\ComponentChain;
use TYPO3\Flow\Http\Component\ComponentContext;
use TYPO3\Flow\Package\Package;

/**
 * A request handler which can handle HTTP requests.
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class RequestHandler implements HttpRequestHandlerInterface
{
    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Component\ComponentChain
     */
    protected $baseComponentChain;

    /**
     * @var Component\ComponentContext
     */
    protected $componentContext;

    /**
     * The "http" settings
     *
     * @var array
     */
    protected $settings;

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
     * @return boolean If the request is a web request, TRUE otherwise FALSE
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
        // Create the request very early so the Resource Management has a chance to grab it:
        $this->request = Request::createFromEnvironment();
        $this->response = new Response();
        $this->componentContext = new ComponentContext($this->request, $this->response);

        $this->boot();
        $this->resolveDependencies();
        $this->addPoweredByHeader($this->response);
        if (isset($this->settings['http']['baseUri'])) {
            $this->request->setBaseUri(new Uri($this->settings['http']['baseUri']));
        }

        $this->baseComponentChain->handle($this->componentContext);
        $this->response = $this->baseComponentChain->getResponse();

        $this->response->send();

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

        $configurationManager = $objectManager->get(ConfigurationManager::class);
        $this->settings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');
    }

    /**
     * Adds an HTTP header to the Response which indicates that the application is powered by Flow.
     *
     * @param Response $response
     * @return void
     */
    protected function addPoweredByHeader(Response $response)
    {
        if ($this->settings['http']['applicationToken'] === 'Off') {
            return;
        }

        $applicationIsFlow = ($this->settings['core']['applicationPackageKey'] === 'TYPO3.Flow');
        if ($this->settings['http']['applicationToken'] === 'ApplicationName') {
            if ($applicationIsFlow) {
                $response->getHeaders()->set('X-Flow-Powered', 'Flow');
            } else {
                $response->getHeaders()->set('X-Flow-Powered', 'Flow ' . $this->settings['core']['applicationName']);
            }
            return;
        }

        /** @var Package $applicationPackage */
        /** @var Package $flowPackage */
        $flowPackage = $this->bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface')->getPackage('TYPO3.Flow');
        $applicationPackage = $this->bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface')->getPackage($this->settings['core']['applicationPackageKey']);

        if ($this->settings['http']['applicationToken'] === 'MajorVersion') {
            $flowVersion = $this->renderMajorVersion($flowPackage->getInstalledVersion());
            $applicationVersion = $this->renderMajorVersion($applicationPackage->getInstalledVersion());
        } else {
            $flowVersion = $this->renderMinorVersion($flowPackage->getInstalledVersion());
            $applicationVersion = $this->renderMinorVersion($applicationPackage->getInstalledVersion());
        }

        if ($applicationIsFlow) {
            $response->getHeaders()->set('X-Flow-Powered', 'Flow/' . ($flowVersion ?: 'dev'));
        } else {
            $response->getHeaders()->set('X-Flow-Powered', 'Flow/' . ($flowVersion ?: 'dev') . ' ' . $this->settings['core']['applicationName'] . '/' . ($applicationVersion ?: 'dev'));
        }
    }

    /**
     * Renders a major version out of a full version string
     *
     * @param string $version For example "2.3.7"
     * @return string For example "2"
     */
    protected function renderMajorVersion($version)
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
    protected function renderMinorVersion($version)
    {
        preg_match('/^(\d+\.\d+)/', $version, $versionMatches);
        return isset($versionMatches[1]) ? $versionMatches[1] : '';
    }
}
