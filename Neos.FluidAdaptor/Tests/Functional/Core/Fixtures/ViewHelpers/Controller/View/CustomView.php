<?php
namespace Neos\FluidAdaptor\Tests\Functional\Core\Fixtures\ViewHelpers\Controller\View;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Response;
use Neos\Flow\Mvc\View\AbstractView;

class CustomView extends AbstractView
{
    public function render()
    {
        return new Response(418, ['X-Flow-Special-Header' => 'YEAH!'], 'Hello World!');
    }
}
