<?php
namespace TYPO3\Flow\Tests\Functional\Mvc\ViewsConfiguration\Fixtures\Controller;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
