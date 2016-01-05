<?php
namespace TYPO3\Flow\Http\Redirection\Aspect;

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
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Http\Redirection\RedirectionService;
use TYPO3\Flow\Log\SystemLoggerInterface;

/**
 * Redirection Storage Aspect
 *
 * This aspect is responsible to emit a signal just after a redirection has been added.
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class RedirectionStorageAspect
{
    /**
     * @Flow\Inject
     * @var RedirectionService
     */
    protected $redirectionService;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\AfterReturning("within(TYPO3\Flow\Http\Redirection\Storage\RedirectionStorageInterface) && method(.*->addRedirection())")
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
     * @return void
     */
    public function emitSignalAfterAddRedirection(JoinPointInterface $joinPoint)
    {
        /** @var Redirection $redirection */
        $redirection = $joinPoint->getResult();
        $this->redirectionService->emitRedirectionCreated($redirection);
        $this->systemLogger->log(sprintf('Redirection from %s -> %s (%d) added', $redirection->getSourceUriPath(), $redirection->getTargetUriPath(), $redirection->getStatusCode()), LOG_DEBUG);
    }
}
