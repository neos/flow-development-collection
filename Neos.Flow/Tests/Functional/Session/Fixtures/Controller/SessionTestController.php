<?php
namespace Neos\Flow\Tests\Functional\Session\Fixtures\Controller;

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
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Session\SessionInterface;

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
