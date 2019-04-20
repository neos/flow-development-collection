<?php
namespace Neos\FluidAdaptor\ViewHelpers\Format;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\Utility\Unicode\Functions as UnicodeUtilityFunctions;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Use this view helper to crop the text between its opening and closing tags.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.crop maxCharacters="10">This is some very long text</f:format.crop>
 * </code>
 * <output>
 * This is so...
 * </output>
 *
 * <code title="Custom suffix">
 * <f:format.crop maxCharacters="17" append=" [more]">This is some very long text</f:format.crop>
 * </code>
 * <output>
 * This is some very [more]
 * </output>
 *
 * <code title="Inline notation">
 * <span title="Location: {user.city -> f:format.crop(maxCharacters: '12')}">John Doe</span>
 * </code>
 * <output>
 * <span title="Location: Newtownmount...">John Doe</span>
 * </output>
 *
 * WARNING: This tag does NOT handle tags currently.
 * WARNING: This tag doesn't care about multibyte charsets currently.
 *
 * @api
 */
class CropViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeChildren = false;

    /**
     * Initialize the arguments.
     *
     * @return void
     * @api
     */
    public function initializeArguments()
    {
        $this->registerArgument('maxCharacters', 'integer', 'Place where to truncate the string', true);
        $this->registerArgument('append', 'string', 'What to append, if truncation happened', false, '...');
        $this->registerArgument('value', 'string', 'The input value which should be cropped. If not set, the evaluated contents of the child nodes will be used', false, null);
    }

    /**
     * Render the cropped text
     *
     * @return string cropped text
     * @api
     */
    public function render()
    {
        return self::renderStatic(['maxCharacters' => $this->arguments['maxCharacters'], 'append' => $this->arguments['append'], 'value' => $this->arguments['value']], $this->buildRenderChildrenClosure(), $this->renderingContext);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $value = $arguments['value'];
        if ($value === null) {
            $value = (string)$renderChildrenClosure();
        }

        if (UnicodeUtilityFunctions::strlen($value) > $arguments['maxCharacters']) {
            return UnicodeUtilityFunctions::substr($value, 0, $arguments['maxCharacters']) . $arguments['append'];
        }
        return $value;
    }
}
