<?php
namespace Neos\Flow\Tests\Functional\Mvc\ViewsConfiguration\Fixtures\Controller;

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
        $this->view->setTemplatePathAndFilename('resource://Neos.Flow/Private/Templates/Tests/Functional/Mvc/Fixtures/ViewsConfigurationTest/First.html');
    }

    /**
     * @return string
     */
    public function widgetAction()
    {
    }
}
