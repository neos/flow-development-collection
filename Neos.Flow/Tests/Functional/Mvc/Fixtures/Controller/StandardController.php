<?php
namespace Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller;

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
use Neos\Error\Messages\Message;

/**
 * A controller fixture
 *
 * @Flow\Scope("singleton")
 */
class StandardController extends ActionController
{
    /**
     * @return string
     */
    public function indexAction()
    {
        return 'index action';
    }

    /**
     * @return string
     */
    public function targetAction()
    {
        $flashMessages = $this->controllerContext->getFlashMessageContainer()->getMessagesAndFlush();
        return json_encode(array_map(static function (Message $message) {
            return $message->getMessage();
        }, $flashMessages));
    }

    /**
     * @return string
     */
    public function redirectWithFlashMessageAction()
    {
        $this->addFlashMessage('Redirect FlashMessage');
        $this->redirect('target');
    }
}
