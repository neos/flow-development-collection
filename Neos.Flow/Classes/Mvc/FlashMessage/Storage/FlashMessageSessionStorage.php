<?php
namespace Neos\Flow\Mvc\FlashMessage\Storage;

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
use Neos\Flow\Mvc\FlashMessage\FlashMessageContainer;
use Neos\Flow\Mvc\FlashMessage\FlashMessageStorageInterface;
use Neos\Flow\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface as HttpRequestInterface;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;

class FlashMessageSessionStorage implements FlashMessageStorageInterface
{
    const DEFAULT_SESSION_KEY = 'Neos_Flow_FlashMessages';

    /**
     * @Flow\Inject
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var array
     */
    private $options;

    /**
     * @var string
     */
    private $sessionKey;

    /**
     * @var FlashMessageContainer|null
     */
    private $flashMessageContainer;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
        $this->sessionKey = $this->options['sessionKey'] ?? self::DEFAULT_SESSION_KEY;
    }

    /**
     * @param HttpRequestInterface $_ Not used in this implementation
     * @return FlashMessageContainer
     */
    public function load(HttpRequestInterface $_): FlashMessageContainer
    {
        if ($this->flashMessageContainer === null) {
            $this->flashMessageContainer = $this->restoreFlashMessageContainerFromSession();
            if ($this->flashMessageContainer === null) {
                $this->flashMessageContainer = new FlashMessageContainer();
            }
        }
        return $this->flashMessageContainer;
    }

    /**
     * @return FlashMessageContainer|null
     */
    private function restoreFlashMessageContainerFromSession(): ?FlashMessageContainer
    {
        if ($this->session->canBeResumed()) {
            $this->session->resume();
        }
        if (!$this->session->isStarted()) {
            return null;
        }
        if (!$this->session->hasKey($this->sessionKey)) {
            return null;
        }
        /** @var FlashMessageContainer $flashMessageContainer */
        $flashMessageContainer = $this->session->getData($this->sessionKey);
        return $flashMessageContainer;
    }

    /**
     * @param HttpResponseInterface $response Not used in this implementation
     * @return HttpResponseInterface
     */
    public function persist(HttpResponseInterface $response): HttpResponseInterface
    {
        if ($this->flashMessageContainer === null) {
            return $response;
        }
        if (!$this->session->isStarted()) {
            // Don't start a new session if the FlashMessageContainer is empty
            if (!$this->flashMessageContainer->hasMessages()) {
                return $response;
            }
            $this->session->start();
        }
        $this->session->putData($this->sessionKey, $this->flashMessageContainer);
        return $response;
    }
}
