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

use Neos\RedirectHandler\Exception;
use Neos\RedirectHandler\RedirectInterface;
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
     * @param array $redirects
     * @return void
     * @throws Exception
     */
    public function emitRedirectionCreated(array $redirects)
    {
        foreach ($redirects as $redirect) {
            if (!$redirect instanceof RedirectInterface) {
                throw new Exception('Redirect should implement RedirectInterface', 1460139669);
            }
            $this->_redirectionService->emitRedirectionCreated($redirect);
            $this->_logger->log(sprintf('Redirect from %s %s -> %s (%d) added', $redirect->getHost(), $redirect->getSourceUriPath(), $redirect->getTargetUriPath(), $redirect->getStatusCode()), LOG_DEBUG);
        }
    }
}
