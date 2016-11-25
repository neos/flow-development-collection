<?php
namespace Neos\Flow\Cli;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * An interface which allows a CommandController to tweak command descriptions before they are displayed to the user.
 *
 * @api
 */
interface DescriptionAwareCommandControllerInterface
{
    /**
     * Processes the given short description of the specified command.
     *
     * @param string $controllerCommandName Name of the command the description is referring to, for example "flush"
     * @param string $shortDescription The short command description so far
     * @param ObjectManagerInterface $objectManager The object manager, can be used to access further information necessary for rendering the description
     * @return string the possibly modified short command description
     * @api
     */
    public static function processShortDescription($controllerCommandName, $shortDescription, ObjectManagerInterface $objectManager);

    /**
     * Processes the given description of the specified command.
     *
     * @param string $controllerCommandName Name of the command the description is referring to, for example "flush"
     * @param string $description The command description so far
     * @param ObjectManagerInterface $objectManager The object manager, can be used to access further information necessary for rendering the description
     * @return string the possibly modified command description
     * @api
     */
    public static function processDescription($controllerCommandName, $description, ObjectManagerInterface $objectManager);
}
