<?php
namespace TYPO3\Fluid\Tests\Functional\Form\Fixtures\Controller;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Controller for simple CRUD actions, to test Fluid forms in
 * combination with Property Mapping
 */
class FormController extends \TYPO3\Flow\Mvc\Controller\ActionController
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
     * @param \TYPO3\Fluid\Tests\Functional\Form\Fixtures\Domain\Model\Post $post
     * @return string
     */
    public function createAction(\TYPO3\Fluid\Tests\Functional\Form\Fixtures\Domain\Model\Post $post)
    {
        return $post->getName() . '|' . $post->getAuthor()->getEmailAddress();
    }

    /**
     * We deliberately use a different variable name in the index action and the create action; as the same variable name is not required!
     *
     * @param \TYPO3\Fluid\Tests\Functional\Form\Fixtures\Domain\Model\Post $fooPost
     * @return void
     * @Flow\IgnoreValidation("$fooPost")
     */
    public function editAction(\TYPO3\Fluid\Tests\Functional\Form\Fixtures\Domain\Model\Post $fooPost = null)
    {
        $this->view->assign('fooPost', $fooPost);
    }

    /**
     * @param \TYPO3\Fluid\Tests\Functional\Form\Fixtures\Domain\Model\Post $post
     * @return string
     */
    public function updateAction(\TYPO3\Fluid\Tests\Functional\Form\Fixtures\Domain\Model\Post $post)
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
