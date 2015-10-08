<?php
namespace TYPO3\Flow\Tests\Functional\Http\Fixtures\Controller;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Mvc\Controller\ActionController;

class RedirectingController extends ActionController
{
    /**
     * @return void
     */
    public function fromHereAction()
    {
        $this->redirect('toHere');
    }

    /**
     * @return void
     */
    public function toHereAction()
    {
        $this->redirect('toThere');
    }

    /**
     * @return string
     */
    public function toThereAction()
    {
        return 'arrived.';
    }
}
