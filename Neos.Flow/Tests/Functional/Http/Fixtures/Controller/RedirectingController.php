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

use Neos\Flow\Mvc\Controller\ActionController;

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
