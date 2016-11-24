<?php
namespace Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Controller;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * Controller for simple CRUD actions, to test Fluid forms in
 * combination with Property Mapping
 */
class FormController extends \Neos\Flow\Mvc\Controller\ActionController
{
    /**
     * Display a start page
     *
     * @return void
     */
    public function indexAction()
    {
    }

    /**
     * @param \Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Domain\Model\Post $post
     * @return string
     */
    public function createAction(\Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Domain\Model\Post $post)
    {
        return $post->getName() . '|' . $post->getAuthor()->getEmailAddress();
    }

    /**
     * We deliberately use a different variable name in the index action and the create action; as the same variable name is not required!
     *
     * @param \Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Domain\Model\Post $fooPost
     * @return void
     * @Flow\IgnoreValidation("$fooPost")
     */
    public function editAction(\Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Domain\Model\Post $fooPost = null)
    {
        $this->view->assign('fooPost', $fooPost);
    }

    /**
     * @param \Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Domain\Model\Post $post
     * @return string
     */
    public function updateAction(\Neos\FluidAdaptor\Tests\Functional\Form\Fixtures\Domain\Model\Post $post)
    {
        return $post->getName() . '|' . $post->getAuthor()->getEmailAddress();
    }

    /**
     * @param string $email
     * @Flow\Validate(argumentName="email", type="EmailAddress")
     * @return string
     */
    public function checkAction($email = null)
    {
        $this->view->assign('email', $email);
    }
}
