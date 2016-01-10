<?php
namespace Neos\RedirectHandler\Aspect;

/*
 * This file is part of the Neos.RedirectHandler package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirection;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Log\SystemLoggerInterface;
use \Neos\RedirectHandler\Redirection as RedirectionDto;

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
     * @var \Neos\RedirectHandler\RedirectionService
     */
    protected $redirectionService;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\AfterReturning("within(Neos\RedirectHandler\Storage\RedirectionStorageInterface) && method(.*->addRedirection())")
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
     * @return void
     */
    public function emitSignalAfterAddRedirection(JoinPointInterface $joinPoint)
    {
        /** @var array<Redirection> $redirection */
        $redirections = $joinPoint->getResult();
        foreach ($redirections as $redirection) {
            /** @var Redirection $redirection */
            $redirectionDto = new RedirectionDto($redirection->getSourceUriPath(), $redirection->getTargetUriPath(), $redirection->getStatusCode(), $redirection->getHostPattern());
            $this->redirectionService->emitRedirectionCreated($redirectionDto);
            $this->systemLogger->log(sprintf('Redirection from %s %s -> %s (%d) added', $redirectionDto->getHostPattern(), $redirectionDto->getSourceUriPath(), $redirectionDto->getTargetUriPath(), $redirectionDto->getStatusCode()), LOG_DEBUG);
        }
    }
}
