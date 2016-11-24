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
 * A controller fixture for testing the AbstractController functionality.
 *
 * Although the functions we want to test are really implemented in the Abstract
 * Controller, this fixture class is an ActionController as this is the easiest way
 * to provide an implementation of the abstract class.
 */
class AbstractControllerTestAController extends ActionController
{
    /**
     * An action which forwards using the given parameters
     *
     * @param string $actionName
     * @param string $controllerName
     * @param string $packageKey
     * @param array $arguments
     * @param boolean $passSomeObjectArguments
     * @return void
     */
    public function forwardAction($actionName, $controllerName = null, $packageKey = null, array $arguments = [], $passSomeObjectArguments = false)
    {
        if ($passSomeObjectArguments) {
            $arguments['__object1'] = new Message('Some test message', 12345);
            $arguments['__object1'] = new Message('Some test message', 67890);
        }
        $this->forward($actionName, $controllerName, $packageKey, $arguments);
    }

    /**
     * @return string
     */
    public function secondAction()
    {
        return 'Second action was called';
    }

    /**
     * @param string $firstArgument
     * @param string $secondArgument
     * @param string $third
     * @param string $fourth
     * @return string
     */
    public function thirdAction($firstArgument, $secondArgument, $third = null, $fourth = 'default')
    {
        return 'thirdAction-' . $firstArgument . '-' . $secondArgument . '-' . $third . '-' . $fourth;
    }

    /**
     *
     * @param string $nonObject1
     * @param integer $nonObject2
     * @return string
     */
    public function fourthAction($nonObject1 = null, $nonObject2 = null)
    {
        $internalArguments = $this->request->getInternalArguments();
        return 'fourthAction-' . $nonObject1 . '-' . $nonObject2 . '-' . (isset($internalArguments['__object1']) ? get_class($internalArguments['__object1']) : 'x');
    }
}
