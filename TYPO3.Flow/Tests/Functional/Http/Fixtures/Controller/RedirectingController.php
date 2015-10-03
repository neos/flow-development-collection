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
