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
 * part of the test fixture for {@see UriBuilderTest}
 *
 * @Flow\Scope("singleton")
 */
class UriBuilderController extends ActionController
{
    /**
     * @return string
     */
    public function differentHostAction()
    {
        return $this->uriBuilder
            ->reset()
            ->uriFor('target', [
                'someRoutePart' => 'foo'
            ], 'UriBuilder', 'Neos.Flow', 'Tests\Functional\Mvc\Fixtures');
    }

    /**
     * @return string
     */
    public function differentHostWithCreateAbsoluteUriAction()
    {
        return $this->uriBuilder
            ->reset()
            ->setCreateAbsoluteUri(true)
            ->uriFor('target', [
                'someRoutePart' => 'foo'
            ], 'UriBuilder', 'Neos.Flow', 'Tests\Functional\Mvc\Fixtures');
    }

    /**
     * @return string
     */
    public function linkingToRootAction()
    {
        return $this->uriBuilder
            ->reset()
            ->uriFor('root', [
            ], 'UriBuilder', 'Neos.Flow', 'Tests\Functional\Mvc\Fixtures');
    }

    /**
     * @return string
     */
    public function linkingToRootWithCreateAbsoluteUriAction()
    {
        return $this->uriBuilder
            ->reset()
            ->setCreateAbsoluteUri(true)
            ->uriFor('root', [], 'UriBuilder', 'Neos.Flow', 'Tests\Functional\Mvc\Fixtures');
    }

    /**
     * @return string
     */
    public function rootAction()
    {
    }

    /**
     * @return string
     */
    public function targetAction()
    {
    }
}
