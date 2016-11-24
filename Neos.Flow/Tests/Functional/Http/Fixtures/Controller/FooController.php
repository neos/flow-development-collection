<?php
namespace Neos\Flow\Tests\Functional\Http\Fixtures\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Controller\AbstractController;
use Neos\Flow\Mvc\RequestInterface;
use Neos\Flow\Mvc\ResponseInterface;

class FooController extends AbstractController
{
    /**
     * Process Request
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return void
     */
    public function processRequest(RequestInterface $request, ResponseInterface $response)
    {
        $this->initializeController($request, $response);
        $response->appendContent('FooController responded');
    }
}
