<?php
namespace Neos\Flow\Tests\Functional\Security\Fixtures\Controller;

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
 * A controller for functional testing
 */
class RestrictedController extends ActionController
{
    /**
     * This action is not restricted in a policy, everybody can access it
     *
     * @return string
     */
    public function publicAction()
    {
        return 'public';
    }

    /**
     * This action is restricted to accounts with the role Customer by a policy in
     * the Flow package's Testing context configuration.
     *
     * @return string
     */
    public function customerAction()
    {
        return 'customer';
    }

    /**
     * This action is restricted to accounts with the role Administrator by a policy in
     * the Flow package's Testing context configuration.
     *
     * @return string
     */
    public function adminAction()
    {
        return 'admin';
    }

    /**
     * @param string $argument1
     * @param string $argument2
     * @return string
     */
    public function argumentsAction($argument1, $argument2 = 'default')
    {
        return sprintf('argument1: %s, argument2: %s', $argument1, $argument2);
    }
}
