<?php
namespace Neos\RedirectHandler\Traits;

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
use Neos\RedirectHandler\Redirection as RedirectionDto;
use TYPO3\Flow\Annotations as Flow;

/**
 * RedirectSignal
 */
trait RedirectSignalTrait
{
    /**
     * @Flow\Inject
     * @var \Neos\RedirectHandler\RedirectionService
     */
    protected $_redirectionService;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Log\SystemLoggerInterface
     */
    protected $_logger;

    /**
     * @param array <Redirection> $redirections
     * @return void
     */
    public function emitRedirectionCreated(array $redirections)
    {
        foreach ($redirections as $redirection) {
            /** @var Redirection $redirection */
            $redirectionDto = new RedirectionDto($redirection->getSourceUriPath(), $redirection->getTargetUriPath(), $redirection->getStatusCode(), $redirection->getHost());
            $this->_redirectionService->emitRedirectionCreated($redirectionDto);
            $this->_logger->log(sprintf('Redirection from %s %s -> %s (%d) added', $redirectionDto->getHost(), $redirectionDto->getSourceUriPath(), $redirectionDto->getTargetUriPath(), $redirectionDto->getStatusCode()), LOG_DEBUG);
        }
    }
}
