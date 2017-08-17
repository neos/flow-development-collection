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
use Neos\Flow\Http\Request as HttpRequest;
use Neos\Flow\Http\Response as HttpResponse;
use Neos\Flow\Mvc\FlashMessage\FlashMessageContainer;
use Neos\Flow\Mvc\FlashMessage\FlashMessageStorageInterface;
use Neos\Flow\Session\SessionInterface;

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
     * @var FlashMessageContainer
     */
    private $flashMessageContainer;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
        $this->sessionKey = isset($this->options['sessionKey']) ? $this->options['sessionKey'] : self::DEFAULT_SESSION_KEY;
    }

    /**
     * @param HttpRequest $_ Not used in this implementation
     * @return FlashMessageContainer
     */
    public function load(HttpRequest $_): FlashMessageContainer
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
    private function restoreFlashMessageContainerFromSession()
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
     * @param HttpResponse $_ Not used in this implementation
     * @return void
     */
    public function persist(HttpResponse $_)
    {
        if ($this->flashMessageContainer === null) {
            return;
        }
        if (!$this->session->isStarted()) {
            // Don't start a new session if the FlashMessageContainer is empty
            if (!$this->flashMessageContainer->hasMessages()) {
                return;
            }
            $this->session->start();
        }
        $this->session->putData($this->sessionKey, $this->flashMessageContainer);
    }
}
