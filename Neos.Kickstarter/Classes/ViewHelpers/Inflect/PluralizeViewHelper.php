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
 * Pluralize a word
 *
 * = Examples =
 *
 * <code title="Example">
 * {variable -> k:inflect.pluralize()}
 * </code>
 *
 * Output:
 * content of {variable} in its plural form (foo => foos)
 *
 */
class PluralizeViewHelper extends \Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper
{
    /**
     * @var \Neos\Kickstarter\Utility\Inflector
     * @Flow\Inject
     */
    protected $inflector;

    /**
     * Pluralize a word
     *
     * @return string The pluralized string
     */
    public function render()
    {
        $content = $this->renderChildren();
        return $this->inflector->pluralize($content);
    }
}
