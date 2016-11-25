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
 * An action controller test fixture
 *
 * @Flow\Scope("singleton")
 */
class ActionControllerTestBController extends ActionController
{
    public function initializeAction()
    {
        $this->arguments['argument']->getPropertyMappingConfiguration()->allowAllProperties();
    }

    /**
     * @param TestObjectArgument $argument
     * @Flow\IgnoreValidation(argumentName="$argument")
     * @return string
     */
    public function showObjectArgumentAction(TestObjectArgument $argument)
    {
        return $argument->getEmailAddress();
    }

    /**
     * @param TestObjectArgument $argument
     * @return string
     */
    public function requiredObjectAction(TestObjectArgument $argument)
    {
        return $argument->getEmailAddress();
    }

    /**
     * @param TestObjectArgument $argument
     * @return string
     */
    public function optionalObjectAction(TestObjectArgument $argument = null)
    {
        if ($argument === null) {
            return 'null';
        }
        return $argument->getEmailAddress();
    }

    /**
     * @param TestObjectArgument $argument
     * @Flow\ValidationGroups({"notValidatedGroup"})
     * @return string
     */
    public function notValidatedGroupObjectAction(TestObjectArgument $argument)
    {
        return $argument->getEmailAddress();
    }

    /**
     * @param TestObjectArgument $argument
     * @Flow\ValidationGroups({"validatedGroup"})
     * @return string
     */
    public function validatedGroupObjectAction(TestObjectArgument $argument)
    {
        return $argument->getEmailAddress();
    }

    /**
     * @param string $argument
     * @return string
     */
    public function requiredStringAction($argument)
    {
        return var_export($argument, true);
    }

    /**
     * @param string $argument
     * @return string
     */
    public function optionalStringAction($argument = 'default')
    {
        return var_export($argument, true);
    }

    /**
     * @param integer $argument
     * @return string
     */
    public function requiredIntegerAction($argument)
    {
        return var_export($argument, true);
    }

    /**
     * @param integer $argument
     * @return string
     */
    public function optionalIntegerAction($argument = 123)
    {
        return var_export($argument, true);
    }

    /**
     * @param float $argument
     * @return string
     */
    public function requiredFloatAction($argument)
    {
        return var_export($argument, true);
    }

    /**
     * @param float $argument
     * @return string
     */
    public function optionalFloatAction($argument = 112.34)
    {
        return var_export($argument, true);
    }

    /**
     * @param \DateTime $argument
     * @return string
     */
    public function requiredDateAction(\DateTime $argument)
    {
        return $argument->format('Y-m-d');
    }

    /**
     * @param \DateTime $argument
     * @return string
     */
    public function optionalDateAction(\DateTime $argument = null)
    {
        if ($argument === null) {
            return 'null';
        }
        return $argument->format('Y-m-d');
    }
}
