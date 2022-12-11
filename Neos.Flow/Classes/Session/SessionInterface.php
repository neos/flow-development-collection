<?php
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
 * Contract for a session.
 */
interface SessionInterface
{
    /**
     * Tells if the session has been started already.
     *
     * @return boolean
     */
    public function isStarted(): bool;

    /**
     * Starts the session, if is has not been already started
     *
     * @return void
     */
    public function start(): void;

    /**
     * Returns true if there is a session that can be resumed. false otherwise
     *
     * @return boolean
     */
    public function canBeResumed(): bool;

    /**
     * Resumes an existing session, if any.
     *
     * @return integer|null
     */
    public function resume(): ?int;

    /**
     * Returns the current session ID.
     *
     * @return string The current session ID
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
     * Returns the contents (array) associated with the given key.
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
     * @return boolean
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
     * @return array<string> The tags or an empty array if there aren't any
     * @throws Exception\SessionNotStartedException
     * @api
     */
    public function getTags(): array;

    /**
     * Updates the last activity time to "now".
     *
     * @return void
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
     * @return integer unix timestamp
     * @api
     */
    public function getLastActivityTimestamp(): int;

    /**
     * Explicitly writes (persists) and closes the session
     *
     * @return void
     * @throws Exception\SessionNotStartedException
     */
    public function close(): void;

    /**
     * Explicitly destroys all session data
     *
     * @param string $reason A reason for destroying the session – used by the LoggingAspect
     * @return void
     * @throws Exception\SessionNotStartedException
     */
    public function destroy(string $reason = null): void;

    /**
     * Remove data of all sessions which are considered to be expired.
     *
     * @return integer|false The number of outdated entries removed or false if no such information could be determined
     */
    public function collectGarbage(): int|false;
}
