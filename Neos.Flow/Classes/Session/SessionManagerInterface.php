<?php
declare(strict_types=1);
namespace Neos\Flow\Session;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Interface for a session manager
 *
 * In order to stay compatible with future features and create more portable apps,
 * make sure to inject this interface instead of the concrete SessionManager
 * implementation.
 *
 * @api
 */
interface SessionManagerInterface
{
    /**
     * Returns the currently active session which stores session data for the
     * current HTTP request on this local system.
     *
     * @api
     */
    public function getCurrentSession(): SessionInterface;

    /**
     * Returns the specified session. If no session with the given identifier exists,
     * NULL is returned.
     *
     * @api
     */
    public function getSession(string $sessionIdentifier): ?SessionInterface;

    /**
     * Returns all active sessions, even remote ones.
     *
     * @return array<Session>
     * @api
     */
    public function getActiveSessions(): array;

    /**
     * Returns all sessions which are tagged by the specified tag.
     *
     * @param string $tag A valid Cache Frontend tag
     * @return array A collection of Session objects or an empty array if tag did not match
     * @api
     */
    public function getSessionsByTag(string $tag): array;

    /**
     * Destroys all sessions which are tagged with the specified tag.
     *
     * @param string $tag A valid Cache Frontend tag
     * @param string $reason A reason to mention in log output for why the sessions have been destroyed. For example: "The corresponding account was deleted"
     * @return int Number of sessions which have been destroyed
     * @api
     */
    public function destroySessionsByTag(string $tag, string $reason = ''): int;

    /**
     * Remove data of all sessions which are considered to be expired.
     *
     * @return ?int The number of outdated entries removed or NULL if no such information could be determined
     */
    public function collectGarbage(): ?int;
}
