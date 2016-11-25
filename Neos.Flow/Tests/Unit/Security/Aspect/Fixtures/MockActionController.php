<?php
namespace Neos\Flow\Security\Aspect\Fixtures;

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

/**
 * A mock ActionController
 *
 */
class MockActionController extends \Neos\Flow\Mvc\Controller\ActionController
{
    /**
     * @return void
     */
    public function actionWithCsrfProtectionAction()
    {
    }

    /**
     * @Flow\SkipCsrfProtection
     * @return void
     */
    public function actionWithoutCsrfProtectionAction()
    {
    }
}
