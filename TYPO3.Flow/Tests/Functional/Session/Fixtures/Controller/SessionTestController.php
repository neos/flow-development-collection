<?php
namespace TYPO3\Flow\Tests\Functional\Session\Fixtures\Controller;

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
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Session\SessionInterface;

/**
 * A controller for functional testing
 */
class SessionTestController extends ActionController
{
    /**
     * @Flow\Inject
     * @var SessionInterface
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
