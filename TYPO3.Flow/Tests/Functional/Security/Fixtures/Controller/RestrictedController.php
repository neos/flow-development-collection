<?php
namespace TYPO3\Flow\Tests\Functional\Security\Fixtures\Controller;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A controller for functional testing
 */
class RestrictedController extends \TYPO3\Flow\Mvc\Controller\ActionController
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
