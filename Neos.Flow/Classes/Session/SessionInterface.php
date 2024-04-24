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

interface SessionInterface
{
    /**
     * Tells if the session has been started already.
     */
    public function isStarted(): bool;

    /**
     * Starts the session, if it has not been already started
     */
    public function start(): void;

    /**
     * Returns true if there is a session that can be resumed. false otherwise
     */
    public function canBeResumed(): bool;

    /**
     * Resumes an existing session, if any.
     *
     * @return null|int If a session was resumed, the inactivity of this session since the last request is returned
     */
    public function resume(): ?int;

    /**
     * Returns the current session ID.
     *
     * @throws Exception\SessionNotStartedException
     */
    public function getId(): string;

    /**
     * Generates and propagates a new session ID and transfers all existing data
     * to the new session.
     *
     * Renewing the session ID is one counter measure against Session Fixation Attacks.
     *
     * @return string The new session ID
     */
    public function renewId(): string;

    /**
     * Returns the content (mixed) associated with the given key.
     *
     * @param string $key An identifier for the content stored in the session.
     * @return mixed The contents associated with the given key
     * @throws Exception\SessionNotStartedException
     */
    public function getData(string $key): mixed;

    /**
     * Returns true if $key is available.
     *
     * @param string $key
     * @return bool
     */
    public function hasKey(string $key): bool;

    /**
     * Stores the given data under the given key in the session
     *
     * @param string $key The key under which the data should be stored
     * @param mixed $data The data to be stored
     * @return void
     * @throws Exception\SessionNotStartedException
     */
    public function putData(string $key, mixed $data): void;

    /**
     * Tags this session with the given tag.
     *
     * Note that third-party libraries might also tag your session. Therefore it is
     * recommended to use namespaced tags such as "Acme-Demo-MySpecialTag".
     *
     * @param string $tag The tag – must match be a valid cache frontend tag
     * @return void
     * @throws Exception\SessionNotStartedException
     * @throws \InvalidArgumentException
     * @api
     */
    public function addTag(string $tag): void;

    /**
     * Removes the specified tag from this session.
     *
     * @param string $tag The tag – must match be a valid cache frontend tag
     * @return void
     * @throws Exception\SessionNotStartedException
     * @api
     */
    public function removeTag(string $tag): void;

    /**
     * Returns the tags this session has been tagged with.
     *
     * @return array The tags or an empty array if there aren't any
     * @throws Exception\SessionNotStartedException
     * @api
     */
    public function getTags(): array;

    /**
     * Updates the last activity time to "now".
     *
     * @api
     */
    public function touch(): void;

    /**
     * Returns the unix time stamp marking the last point in time this session has
     * been in use.
     *
     * For the current (local) session, this method will always return the current
     * time. For a remote session, the unix timestamp will be returned.
     *
     * @return int unix timestamp
     * @api
     */
    public function getLastActivityTimestamp(): int;

    /**
     * Explicitly writes (persists) and closes the session
     *
     * @throws Exception\SessionNotStartedException
     */
    public function close(): void;

    /**
     * Explicitly destroys all session data
     *
     * @param string|null $reason A reason for destroying the session – used by the LoggingAspect
     * @return void
     * @throws Exception\SessionNotStartedException
     */
    public function destroy(?string $reason = null): void;
}
