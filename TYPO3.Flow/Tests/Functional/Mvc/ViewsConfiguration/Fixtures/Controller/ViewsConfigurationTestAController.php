<?php
namespace TYPO3\Flow\Tests\Functional\Mvc\ViewsConfiguration\Fixtures\Controller;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;

/**
 * A controller fixture
 */
class ViewsConfigurationTestAController extends ActionController
{
    /**
     * @return string
     */
    public function firstAction()
    {
    }

    /**
     * @return string
     */
    public function secondAction()
    {
    }

    /**
     * @return string
     */
    public function viewClassAction()
    {
        return get_class($this->view);
    }

    /**
     * @return string
     */
    public function renderOtherAction()
    {
        $this->view->setTemplatePathAndFilename('resource://TYPO3.Flow/Private/Templates/Tests/Functional/Mvc/Fixtures/ViewsConfigurationTest/First.html');
    }

    /**
     * @return string
     */
    public function widgetAction()
    {
    }
}
