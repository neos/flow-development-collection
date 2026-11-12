<?php

declare(strict_types=1);

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
 * A controller for testing that the error action is only called if the requested action is accessible
 *
 * CSRF protection is skipped, as it is not the subject of these tests.
 */
class ErrorActionPolicyTestController extends ActionController
{
    /**
     * This action is not restricted in a policy, everybody can access it
     *
     * @Flow\Validate(argumentName="emailAddress", type="EmailAddress")
     * @Flow\SkipCsrfProtection
     */
    public function publicAction(string $emailAddress): string
    {
        return 'public';
    }

    /**
     * This action is restricted to accounts with the role Customer by a policy in
     * the Flow package's Testing context configuration.
     *
     * @Flow\Validate(argumentName="emailAddress", type="EmailAddress")
     * @Flow\SkipCsrfProtection
     */
    public function restrictedAction(string $emailAddress): string
    {
        return 'restricted';
    }

    /**
     * Throws an exception with a distinct code, so tests can tell for sure whether the error action was called
     *
     * @return string
     */
    protected function errorAction()
    {
        throw new \RuntimeException('FAIL: The error action was called', 1794502055);
    }
}
