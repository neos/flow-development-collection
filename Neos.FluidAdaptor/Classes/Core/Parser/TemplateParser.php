<?php
namespace Neos\FluidAdaptor\Core\Parser;

use TYPO3Fluid\Fluid\Core\Parser\InterceptorInterface;
use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
use TYPO3Fluid\Fluid\Core\Parser\Patterns;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode;

/**
 * This is needed to support the EscapingFlagProcessor and globally (en|dis)able escaping in the template.
 */
class TemplateParser extends \TYPO3Fluid\Fluid\Core\Parser\TemplateParser
{
    /**
     * @return boolean
     */
    public function isEscapingEnabled()
    {
        return $this->escapingEnabled;
    }

    /**
     * @param boolean $escapingEnabled
     */
    public function setEscapingEnabled($escapingEnabled)
    {
        $this->escapingEnabled = $escapingEnabled;
    }

    /**
     * Handles the appearance of an object accessor (like {posts.author.email}).
     * Creates a new instance of \TYPO3Fluid\Fluid\ObjectAccessorNode.
     *
     * Handles ViewHelpers as well which are in the shorthand syntax.
     *
     * @param ParsingState $state The current parsing state
     * @param string $objectAccessorString String which identifies which objects to fetch
     * @param string $delimiter
     * @param string $viewHelperString
     * @param string $additionalViewHelpersString
     * @return void
     */
    protected function objectAccessorHandler(ParsingState $state, $objectAccessorString, $delimiter, $viewHelperString, $additionalViewHelpersString)
    {
        $viewHelperString .= $additionalViewHelpersString;
        $numberOfViewHelpers = 0;

        // The following post-processing handles a case when there is only a ViewHelper, and no Object Accessor.
        // Resolves bug #5107.
        if (strlen($delimiter) === 0 && strlen($viewHelperString) > 0) {
            $viewHelperString = $objectAccessorString . $viewHelperString;
            $objectAccessorString = '';
        }

        // ViewHelpers
        $matches = [];
        if (strlen($viewHelperString) > 0 && preg_match_all(Patterns::$SPLIT_PATTERN_SHORTHANDSYNTAX_VIEWHELPER, $viewHelperString, $matches, PREG_SET_ORDER) > 0) {
            // The last ViewHelper has to be added first for correct chaining.
            foreach (array_reverse($matches) as $singleMatch) {
                if (strlen($singleMatch['ViewHelperArguments']) > 0) {
                    $arguments = $this->recursiveArrayHandler($singleMatch['ViewHelperArguments']);
                } else {
                    $arguments = [];
                }
                $viewHelperNode = $this->initializeViewHelperAndAddItToStack($state, $singleMatch['NamespaceIdentifier'], $singleMatch['MethodIdentifier'], $arguments);
                if ($viewHelperNode) {
                    $numberOfViewHelpers++;
                }
            }
        }

        // Object Accessor
        if (strlen($objectAccessorString) > 0) {
            // FIXME: This is the only alteration in this method to protected against: https://github.com/TYPO3/Fluid/issues/255
            $accessors = [];
            $node = new ObjectAccessorNode($objectAccessorString, $accessors);
            $this->callInterceptor($node, InterceptorInterface::INTERCEPT_OBJECTACCESSOR, $state);
            $state->getNodeFromStack()->addChildNode($node);
        }

        // Close ViewHelper Tags if needed.
        for ($i = 0; $i < $numberOfViewHelpers; $i++) {
            $node = $state->popNodeFromStack();
            $this->callInterceptor($node, InterceptorInterface::INTERCEPT_CLOSING_VIEWHELPER, $state);
            $state->getNodeFromStack()->addChildNode($node);
        }
    }
}
