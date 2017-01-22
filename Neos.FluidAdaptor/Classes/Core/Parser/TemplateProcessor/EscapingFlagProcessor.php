<?php
namespace Neos\FluidAdaptor\Core\Parser\TemplateProcessor;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3Fluid\Fluid\Core\Parser\TemplateProcessorInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use Neos\FluidAdaptor\Core\Exception;

/**
 * Preprocessor to detect the "escapingEnabled" inline flag in a template.
 */
class EscapingFlagProcessor implements TemplateProcessorInterface
{
    /**
     * @var RenderingContextInterface
     */
    protected $renderingContext;

    public static $SCAN_PATTERN_ESCAPINGMODIFIER = '/{escapingEnabled\s*=\s*(?P<enabled>true|false)\s*}/i';

    /**
     * @param RenderingContextInterface $renderingContext
     */
    public function setRenderingContext(RenderingContextInterface $renderingContext)
    {
        $this->renderingContext = $renderingContext;
    }

    /**
     * Pre-process the template source before it is
     * returned to the TemplateParser or passed to
     * the next TemplateProcessorInterface instance.
     *
     * @param string $templateSource
     * @return string
     */
    public function preProcessSource($templateSource)
    {
        $matches = [];
        preg_match_all(self::$SCAN_PATTERN_ESCAPINGMODIFIER, $templateSource, $matches, PREG_SET_ORDER);
        if ($matches === []) {
            return $templateSource;
        }
        if (count($matches) > 1) {
            throw new Exception('There is more than one escaping modifier defined. There can only be one {escapingEnabled=...} per template.', 1407331080);
        }
        if (strtolower($matches[0]['enabled']) === 'false') {
            $this->renderingContext->getTemplateParser()->setEscapingEnabled(false);
        }
        $templateSource = preg_replace(self::$SCAN_PATTERN_ESCAPINGMODIFIER, '', $templateSource);

        return $templateSource;
    }
}
