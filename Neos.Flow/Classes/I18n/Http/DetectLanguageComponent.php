<?php

namespace Neos\Flow\I18n\Http;

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
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\ComponentInterface;
use Neos\Flow\I18n\Detector;
use Neos\Flow\I18n\Exception\InvalidLocaleIdentifierException;
use Neos\Flow\I18n\Locale;
use Neos\Flow\Mvc\Routing\RoutingComponent;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A HTTP component that detects and sets the current locale from request URI, cookie or Accept-Language header.
 */
class DetectLanguageComponent implements ComponentInterface
{
    /**
     * @var Detector
     * @Flow\Inject
     */
    protected $localeDetector;

    /**
     * @var \Neos\Flow\I18n\Service
     * @Flow\Inject
     */
    protected $i18nService;

    /**
     * @var \Neos\Flow\Log\SystemLoggerInterface
     * @Flow\Inject
     */
    protected $logger;

    /**
     * @var array
     */
    protected $options;

    protected static $defaultOptions = array(
        //'uriPosition' => 0,
        'argumentName' => 'locale',
        'cookieName' => 'locale',
        'headerName' => 'X-Locale'
    );

    /**
     * @param array $options The component options
     */
    public function __construct(array $options = array())
    {
        $this->options = array_merge(self::$defaultOptions, $options);
    }

    /**
     * @param ServerRequestInterface $httpRequest
     * @return Locale
     */
    protected function retrieveLocaleFromRequest(ServerRequestInterface $httpRequest)
    {
        $queryParams = $httpRequest->getQueryParams();
        if ($this->options['argumentName'] !== '' && isset($queryParams[$this->options['argumentName']])) {
            try {
                $localeArgument = $queryParams[$this->options['argumentName']];
                $argumentLocale = $this->localeDetector->detectLocaleFromTemplateLocale(new Locale($localeArgument));
                return $argumentLocale;
            } catch (InvalidLocaleIdentifierException $exception) {
            }
        }

        $cookieParams = $httpRequest->getCookieParams();
        if ($this->options['cookieName'] !== '' && isset($cookieParams[$this->options['cookieName']])) {
            $localeCookie = $cookieParams[$this->options['cookieName']];
            try {
                $cookieLocale = $this->localeDetector->detectLocaleFromTemplateLocale(new Locale($localeCookie));
                return $cookieLocale;
            } catch (InvalidLocaleIdentifierException $exception) {
            }
        }

        if ($this->options['headerName'] !== '' && $httpRequest->hasHeader($this->options['headerName'])) {
            $localeHeader = $httpRequest->getHeader($this->options['headerName']);
            try {
                $headerLocale = $this->localeDetector->detectLocaleFromTemplateLocale(new Locale($localeHeader));
                return $headerLocale;
            } catch (InvalidLocaleIdentifierException $exception) {
            }
        }

        return $this->localeDetector->detectLocaleFromHttpHeader($httpRequest->getHeader('Accept-Language'));
    }

    /**
     * @param ComponentContext $componentContext
     * @return void
     */
    public function handle(ComponentContext $componentContext)
    {
        $httpRequest = $componentContext->getHttpRequest();

        $matchResults = $componentContext->getParameter(RoutingComponent::class, 'matchResults');
        if (isset($matchResults['@locale']) && !empty($matchResults['@locale'])) {
            $this->logger->log('Got locale from Route: ' . var_export($matchResults['@locale'], true));
            try {
                $uriLocale = new Locale($matchResults['@locale']);
                $uriLocale = $this->localeDetector->detectLocaleFromTemplateLocale($uriLocale);
                $this->i18nService->getConfiguration()->setCurrentLocale($uriLocale);

                return;
            } catch (InvalidLocaleIdentifierException $exception) {
            }
        }

        $detectedLocale = $this->retrieveLocaleFromRequest($httpRequest);
        $matchResults['@locale'] = (string)$detectedLocale;
        $componentContext->setParameter(RoutingComponent::class, 'matchResults', $matchResults);
        $this->i18nService->getConfiguration()->setCurrentLocale($detectedLocale);
    }
}
