<?php
namespace TYPO3\Flow\Security\Aspect\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A mock ActionController
 *
 */
class MockActionController extends \TYPO3\Flow\Mvc\Controller\ActionController
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
