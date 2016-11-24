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
class ViewsConfigurationTestBController extends ActionController
{
    /**
     * @return string
     */
    public function firstAction()
    {
    }
}
