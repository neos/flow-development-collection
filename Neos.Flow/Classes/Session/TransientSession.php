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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;

/**
 * Implementation of a transient session.
 *
 * This session behaves like any other session except that it only stores the
 * data during one request.
 *
 * @Flow\Scope("singleton")
 */
class TransientSession implements SessionInterface
{
    /**
     * The session Id
     *
     * @var string
     */
    protected $sessionId;

    /**
     * If this session has been started
     *
     * @var boolean
     */
    protected $started = false;

    /**
     * The session data
     *
     * @var array
     */
    protected $data = [];

    /**
     * @var integer
     */
    protected $lastActivityTimestamp;

    /**
     * @var array
     */
    protected $tags;

    /**
     * Tells if the session has been started already.
     *
     * @return boolean
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * Starts the session, if it has not been already started
     *
     * @return void
     */
    public function start()
    {
        $this->sessionId = uniqid();
        $this->started = true;
    }

    /**
     * Returns TRUE if there is a session that can be resumed. FALSE otherwise
     *
     * @return boolean
     */
    public function canBeResumed()
    {
        return true;
    }

    /**
     * Resumes an existing session, if any.
     *
     * @return void
     */
    public function resume()
    {
        if ($this->started === false) {
            $this->start();
        }
    }

    /**
     * Generates and propagates a new session ID and transfers all existing data
     * to the new session.
     *
     * @return string The new session ID
     */
    public function renewId()
    {
        $this->sessionId = uniqid();
        return $this->sessionId;
    }

    /**
     * Returns the current session ID.
     *
     * @return string The current session ID
     * @throws Exception\SessionNotStartedException
     */
    public function getId()
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('The session has not been started yet.', 1218034659);
        }
        return $this->sessionId;
    }

    /**
     * Returns the data associated with the given key.
     *
     * @param string $key An identifier for the content stored in the session.
     * @return mixed The data associated with the given key or NULL
     * @throws Exception\SessionNotStartedException
     */
    public function getData($key)
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('The session has not been started yet.', 1218034660);
        }
        return (array_key_exists($key, $this->data)) ? $this->data[$key] : null;
    }

    /**
     * Returns TRUE if $key is available.
     *
     * @param string $key
     * @return boolean
     */
    public function hasKey($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Stores the given data under the given key in the session
     *
     * @param string $key The key under which the data should be stored
     * @param object $data The data to be stored
     * @return void
     * @throws Exception\SessionNotStartedException
     */
    public function putData($key, $data)
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('The session has not been started yet.', 1218034661);
        }
        $this->data[$key] = $data;
    }

    /**
     * Closes the session
     *
     * @return void
     * @throws Exception\SessionNotStartedException
     */
    public function close()
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('The session has not been started yet.', 1218034662);
        }
        $this->started = false;
    }

    /**
     * Explicitly destroys all session data
     *
     * @param string $reason A reason for destroying the session – used by the LoggingAspect
     * @return void
     * @throws Exception\SessionNotStartedException
     */
    public function destroy($reason = null)
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('The session has not been started yet.', 1218034663);
        }
        $this->data = [];
        $this->started = false;
    }

    /**
     * No operation for transient session.
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function destroyAll(Bootstrap $bootstrap)
    {
    }

    /**
     * No operation for transient session.
     *
     * @return void
     */
    public function collectGarbage()
    {
    }

    /**
     * Returns the unix time stamp marking the last point in time this session has
     * been in use.
     *
     * @return integer unix timestamp
     */
    public function getLastActivityTimestamp()
    {
        if ($this->lastActivityTimestamp === null) {
            $this->touch();
        }
        return $this->lastActivityTimestamp;
    }

    /**
     * Updates the last activity time to "now".
     *
     * @return void
     */
    public function touch()
    {
        $this->lastActivityTimestamp = time();
    }

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
    public function addTag($tag)
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('The session has not been started yet.', 1422551048);
        }
        $this->tags[$tag] = true;
    }

    /**
     * Removes the specified tag from this session.
     *
     * @param string $tag The tag – must match be a valid cache frontend tag
     * @return void
     * @throws Exception\SessionNotStartedException
     * @api
     */
    public function removeTag($tag)
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('The session has not been started yet.', 1422551049);
        }
        if (isset($this->tags[$tag])) {
            unset($this->tags[$tag]);
        }
    }

    /**
     * Returns the tags this session has been tagged with.
     *
     * @return array The tags or an empty array if there aren't any
     * @throws Exception\SessionNotStartedException
     * @api
     */
    public function getTags()
    {
        if ($this->started !== true) {
            throw new Exception\SessionNotStartedException('The session has not been started yet.', 1422551050);
        }
        return array_keys($this->tags);
    }
}
