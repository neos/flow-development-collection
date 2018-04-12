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
class HumanizeCamelCaseViewHelper extends \Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper
{
    /**
     * @var \Neos\Kickstarter\Utility\Inflector
     * @Flow\Inject
     */
    protected $inflector;

    /**
     * Humanize a model name
     *
     * @param boolean $lowercase Wether the result should be lowercased
     * @return string The humanized string
     */
    public function render($lowercase = false)
    {
        $content = $this->renderChildren();
        return $this->inflector->humanizeCamelCase($content, $lowercase);
    }
}
