<?php
namespace TYPO3\Flow\Tests\Functional\Http\Fixtures\Controller;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

class FooController extends \TYPO3\Flow\Mvc\Controller\AbstractController
{
    /**
     * Process Request
     *
     * @param \TYPO3\Flow\Mvc\RequestInterface $request
     * @param \TYPO3\Flow\Mvc\ResponseInterface $response
     * @return void
     */
    public function processRequest(\TYPO3\Flow\Mvc\RequestInterface $request, \TYPO3\Flow\Mvc\ResponseInterface $response)
    {
        $this->initializeController($request, $response);
        $response->appendContent('FooController responded');
    }
}
