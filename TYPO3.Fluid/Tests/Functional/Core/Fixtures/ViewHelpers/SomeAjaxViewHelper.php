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
 * A view helper for the test AJAX widget
 */
class SomeAjaxViewHelper extends AbstractWidgetViewHelper
{
    /**
     * @var boolean
     */
    protected $ajaxWidget = true;

    /**
     * @Flow\Inject
     * @var \TYPO3\Fluid\Tests\Functional\Core\Fixtures\ViewHelpers\Controller\SomeAjaxController
     */
    protected $controller;

    /**
     * The actual render method does nothing more than initiating the sub request
     * which invokes the controller.
     *
     * @param string $option1 Option for testing if parameters can be passed
     * @param string $option2 Option for testing if parameters can be passed
     * @return string
     */
    public function render($option1 = '', $option2 = '')
    {
        return $this->initiateSubRequest();
    }
}
