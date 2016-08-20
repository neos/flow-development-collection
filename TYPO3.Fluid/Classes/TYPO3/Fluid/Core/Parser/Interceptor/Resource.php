<?php
namespace TYPO3\Fluid\Core\Parser\Interceptor;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Package\Package;
use TYPO3\Fluid\Core\Parser\InterceptorInterface;
use TYPO3\Fluid\Core\Parser\ParsingState;
use TYPO3\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;

/**
 * This interceptor looks for URIs pointing to package resources and in place
 * of those adds ViewHelperNode instances using the ResourceViewHelper to
 * make those URIs work in the rendered template.
 *
 * That means you can build your template so that it can be previewed as is and
 * pointers to CSS, JS, images, ... will still work when the resources are
 * mirrored by Flow.
 *
 * Currently the supported URIs are of the form
 *  [../]Public/Some/<Path/To/Resource> (will use current package)
 *  [../]<PackageKey>/Resources/Public/<Path/To/Resource> (will use given package)
 *
 */
class Resource implements InterceptorInterface
{
    /**
     * Split a text at what seems to be a package resource URI.
     * @var string
     */
    const PATTERN_SPLIT_AT_RESOURCE_URIS = '!
		(
			(?:[^"\'(\s]+/      # URL part: A string with no quotes, no opening parentheses and no whitespace
			)*                  # a URL consists of multiple URL parts
			Public/             # the string "Public/"
			[\w._ /]+           # followed by any number of [\w._ /]
		)
		!ux';

    /**
     * Is the text at hand a resource URI and what are path/package?
     * @var string
     * @see \TYPO3\Flow\Pckage\Package::PATTERN_MATCH_PACKAGEKEY
     */
    const PATTERN_MATCH_RESOURCE_URI = '!(?:../)*(?:(?P<Package>[A-Za-z0-9]+\.(?:[A-Za-z0-9][\.a-z0-9]*)+)/Resources/)?Public/(?P<Path>[^"]+)!';

    /**
     * The default package key to use when rendering resource links without a
     * package key in the source URL.
     * @var string
     */
    protected $defaultPackageKey;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Inject object factory
     *
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Set the default package key to use for resource URIs.
     *
     * @param string $defaultPackageKey
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setDefaultPackageKey($defaultPackageKey)
    {
        if (!preg_match(Package::PATTERN_MATCH_PACKAGEKEY, $defaultPackageKey)) {
            throw new \InvalidArgumentException('The given argument was not a valid package key.', 1277287099);
        }
        $this->defaultPackageKey = $defaultPackageKey;
    }

    /**
     * Looks for URIs pointing to package resources and in place of those adds
     * ViewHelperNode instances using the ResourceViewHelper.
     *
     * @param NodeInterface $node
     * @param integer $interceptorPosition One of the INTERCEPT_* constants for the current interception point
     * @param ParsingState $parsingState the current parsing state. Not needed in this interceptor.
     * @return NodeInterface the modified node
     */
    public function process(NodeInterface $node, $interceptorPosition, ParsingState $parsingState)
    {
        /** @var $node TextNode */
        if (strpos($node->getText(), 'Public/') === false) {
            return $node;
        }
        $textParts = preg_split(self::PATTERN_SPLIT_AT_RESOURCE_URIS, $node->getText(), -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $node = $this->objectManager->get(\TYPO3\Fluid\Core\Parser\SyntaxTree\RootNode::class);
        foreach ($textParts as $part) {
            $matches = array();
            if (preg_match(self::PATTERN_MATCH_RESOURCE_URI, $part, $matches)) {
                $arguments = array(
                    'path' => $this->objectManager->get(\TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode::class, $matches['Path'])
                );
                if (isset($matches['Package']) && preg_match(Package::PATTERN_MATCH_PACKAGEKEY, $matches['Package'])) {
                    $arguments['package'] = $this->objectManager->get(\TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode::class, $matches['Package']);
                } elseif ($this->defaultPackageKey !== null) {
                    $arguments['package'] = $this->objectManager->get(\TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode::class, $this->defaultPackageKey);
                }
                $viewHelper = $this->objectManager->get(\TYPO3\Fluid\ViewHelpers\Uri\ResourceViewHelper::class);
                /** @var $viewHelperNode ViewHelperNode */
                $viewHelperNode = $this->objectManager->get(\TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode::class, $viewHelper, $arguments);
                $node->addChildNode($viewHelperNode);
            } else {
                /** @var $textNode TextNode */
                $textNode = $this->objectManager->get(\TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode::class, $part);
                $node->addChildNode($textNode);
            }
        }

        return $node;
    }

    /**
     * This interceptor wants to hook into text nodes.
     *
     * @return array Array of INTERCEPT_* constants
     */
    public function getInterceptionPoints()
    {
        return array(
            InterceptorInterface::INTERCEPT_TEXT
        );
    }
}
