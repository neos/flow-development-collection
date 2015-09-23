<?php
namespace TYPO3\Fluid\Tests\Functional\Core\Fixtures\ViewHelpers;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\Core\Widget\AbstractWidgetViewHelper;

/**
 * A view helper for the redirect test widget
 */
class RedirectViewHelper extends AbstractWidgetViewHelper
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Fluid\Tests\Functional\Core\Fixtures\ViewHelpers\Controller\RedirectController
     */
    protected $controller;

    /**
     * The actual render method does nothing more than initiating the sub request
     * which invokes the controller.
     *
     * @return string
     */
    public function render()
    {
        return $this->initiateSubRequest();
    }
}
