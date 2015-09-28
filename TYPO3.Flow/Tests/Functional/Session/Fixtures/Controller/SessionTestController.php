<?php
namespace TYPO3\Flow\Tests\Functional\Session\Fixtures\Controller;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;

/**
 * A controller for functional testing
 */
class SessionTestController extends ActionController
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Session\SessionInterface
     */
    protected $session;

    /**
     * @Flow\Session(autoStart = true)
     * @return string
     */
    public function sessionStartAction()
    {
        return 'this action started session ' . $this->session->getId();
    }
}
