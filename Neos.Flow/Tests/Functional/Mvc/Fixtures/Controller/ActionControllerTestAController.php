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

/**
 * A controller fixture
 *
 * @Flow\Scope("singleton")
 */
class ActionControllerTestAController extends ActionController
{
    /**
     * @var array
     */
    protected $supportedMediaTypes = [
        'text/html', 'application/json'
    ];

    /**
     * @return string
     */
    public function firstAction()
    {
        return 'First action was called';
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
     * @param string $emailAddress
     * @return void
     */
    public function fourthAction($emailAddress)
    {
        $this->view->assign('emailAddress', $emailAddress);
    }

    /**
     * @param string $putArgument
     * @param string $getArgument
     * @return string
     */
    public function putAction($putArgument, $getArgument)
    {
        return 'putAction-' . $putArgument . '-' . $getArgument;
    }

    /**
     * @Flow\Validate("brokenArgument1", type="StringLength", options={"maximum": 3})
     * @Flow\Validate("brokenArgument2", type="StringLength", options={"minimum": 100})
     * @Flow\IgnoreValidation("brokenArgument1")
     * @Flow\IgnoreValidation("$brokenArgument2")
     * @param string $brokenArgument1
     * @param string $brokenArgument2
     * @return string
     */
    public function ignoreValidationAction($brokenArgument1, $brokenArgument2)
    {
        return 'action was called';
    }

    /**
     * A method with a very short name, to make sure that the ActionController code
     * does not choke on it.
     *
     * @return void
     * @see http://forge.typo3.org/issues/47469
     */
    public function b()
    {
    }
}
