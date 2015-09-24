<?php
namespace TYPO3\Flow\Tests\Functional\Http\Fixtures\Controller;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
