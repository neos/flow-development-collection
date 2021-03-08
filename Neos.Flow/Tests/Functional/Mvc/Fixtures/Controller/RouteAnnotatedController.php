<?php
namespace Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller;

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
 * A route annotated controller fixture
 *
 * @Flow\Scope("singleton")
 */
class RouteAnnotatedController extends ActionController
{
    /**
     * @Flow\Route(uriPattern="annotated/uri/pattern")
     * @return string
     */
    public function annotatedWithUriPatternAction()
    {
        return 'Hello';
    }
}
