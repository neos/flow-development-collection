<?php
namespace TYPO3\Flow\Session;

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

/**
 * Interface for a session manager
 *
 * In order to stay compatible with future features and create more portable apps,
 * make sure to inject this interface instead of the concrete SessionManager
 * implementation.
 */
interface SessionManagerInterface
{
    /**
     * Returns the currently active session which stores session data for the
     * current HTTP request on this local system.
     *
     * @return \TYPO3\Flow\Session\SessionInterface
     */
    public function getCurrentSession();

    /**
     * Returns the specified session. If no session with the given identifier exists,
     * NULL is returned.
     *
     * @param string $sessionIdentifier The session identifier
     * @return \TYPO3\Flow\Session\SessionInterface
     */
    public function getSession($sessionIdentifier);

    /**
     * Returns all active sessions, even remote ones.
     *
     * @return array<\TYPO3\Flow\Session\Session>
     * @api
     */
    public function getActiveSessions();
}
