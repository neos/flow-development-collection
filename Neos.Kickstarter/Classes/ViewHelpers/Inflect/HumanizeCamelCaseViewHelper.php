<?php
namespace Neos\Kickstarter\ViewHelpers\Inflect;

/*
 * This file is part of the Neos.Kickstarter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;

/**
 * Humanize a camel cased value
 *
 * = Examples =
 *
 * <code title="Example">
 * {CamelCasedModelName -> k:inflect.humanizeCamelCase()}
 * </code>
 *
 * Output:
 * Camel cased model name
 *
 */
class HumanizeCamelCaseViewHelper extends AbstractViewHelper
{
    /**
     * @var \Neos\Kickstarter\Utility\Inflector
     * @Flow\Inject
     */
    protected $inflector;

    /**
     * Initialize the arguments.
     *
     * @return void
     * @throws \Neos\FluidAdaptor\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('lowercase', 'bool', 'Whether the result should be lowercased', false, false);
    }

    /**
     * Humanize a model name
     *
     * @return string The humanized string
     */
    public function render(): string
    {
        $content = $this->renderChildren();
        return $this->inflector->humanizeCamelCase($content, $this->arguments['lowercase']);
    }
}
