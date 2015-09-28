<?php
namespace TYPO3\Fluid\Tests\Functional\Core\Fixtures\Controller;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Mvc\Controller\ActionController;

/**
 * This is a regular action controller which serves as the starting point for testing
 * the redirect/forward behavior of widgets.
 */
class RedirectTestController extends ActionController
{
    /**
     * Includes the widget through its Index.html template and renders it.
     *
     * @return string
     */
    public function indexAction()
    {
    }
}
