<?php
namespace TYPO3\Flow\Http\Redirection;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Component\ComponentChain;
use TYPO3\Flow\Http\Component\ComponentContext;
use TYPO3\Flow\Http\Component\ComponentInterface;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;

/**
 * Redirection HTTP Component
 */
class RedirectionComponent implements ComponentInterface
{
    /**
     * @var RouterCachingService
     * @Flow\Inject
     */
    protected $routerCachingService;

    /**
     * @var RedirectionService
     * @Flow\Inject
     */
    protected $redirectionService;

    /**
     * Check if the current request need a redirection
     *
     * @param ComponentContext $componentContext
     * @return void
     */
    public function handle(ComponentContext $componentContext)
    {
        $httpRequest = $componentContext->getHttpRequest();
        $cachedMatchResults = $this->routerCachingService->getCachedMatchResults($httpRequest);
        if ($cachedMatchResults !== false) {
            return;
        }
        $response = $this->redirectionService->buildResponseIfApplicable($httpRequest);
        if ($response !== null) {
            $componentContext->replaceHttpResponse($response);
            $componentContext->setParameter(ComponentChain::class, 'cancel', true);
        }
    }
}
