<?php
namespace Neos\FluidAdaptor\Core\Parser\Interceptor;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Package\Package;
use Neos\FluidAdaptor\Core\Parser\SyntaxTree\ResourceUriNode;
use TYPO3Fluid\Fluid\Core\Parser\InterceptorInterface;
use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\TextNode;

/**
 * This interceptor looks for URIs pointing to package resources and in place
 * of those adds ResourceUriNode instances using the ResourceViewHelper to
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
class ResourceInterceptor implements InterceptorInterface
{

    /**
     * Split a text at what seems to be a package resource URI.
     *
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
     *
     * @var string
     * @see \Neos\Flow\Pckage\Package::PATTERN_MATCH_PACKAGEKEY
     */
    const PATTERN_MATCH_RESOURCE_URI = '!(?:../)*(?:(?P<Package>[A-Za-z0-9]+\.(?:[A-Za-z0-9][\.a-z0-9]*)+)/Resources/)?Public/(?P<Path>[^"]+)!';

    /**
     * The default package key to use when rendering resource links without a
     * package key in the source URL.
     *
     * @var string
     */
    protected $defaultPackageKey;

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
        $node = new RootNode();
        foreach ($textParts as $part) {
            $matches = [];
            if (preg_match(self::PATTERN_MATCH_RESOURCE_URI, $part, $matches)) {
                $arguments = [
                    'path' => new TextNode($matches['Path'])
                ];

                if ($this->defaultPackageKey !== null) {
                    $arguments['package'] = new TextNode($this->defaultPackageKey);
                }
                if (isset($matches['Package']) && preg_match(Package::PATTERN_MATCH_PACKAGEKEY, $matches['Package'])) {
                    $arguments['package'] = new TextNode($matches['Package']);
                }

                $resourceUriNode = new ResourceUriNode($arguments, $parsingState);
                $node->addChildNode($resourceUriNode);
            } else {
                $textNode = new TextNode($part);
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
        return [
            InterceptorInterface::INTERCEPT_TEXT
        ];
    }
}
