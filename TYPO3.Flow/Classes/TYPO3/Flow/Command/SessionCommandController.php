<?php
namespace TYPO3\Flow\Command;

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
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Session\SessionManagerInterface;

/**
 * Command controller for managing sessions
 *
 * @Flow\Scope("singleton")
 */
class SessionCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * Collects the garbage sessions that have expired
     *
     * This is intended for big applications, as running garbage collection over
     * potentially hundreds of thousands of sessions every few requests isn't
     * something you want to do in a production environment. Setup a cronjob
     * instead that calls this command whenever.
     *
     * @return void
     */
    public function collectGarbageCommand()
    {
        $count = $this->sessionManager->getCurrentSession()->collectGarbage();
        $this->outputLine('Removed %d expired sessions.', [$count]);
    }
}