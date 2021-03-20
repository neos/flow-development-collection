<?php
namespace Neos\FluidAdaptor\Core\ViewHelper;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Tag based view helper.
 * Should be used as the base class for all view helpers which output simple tags, as it provides some
 * convenience methods to register default attributes, ...
 *
 * @api
 */
abstract class AbstractTagBasedViewHelper extends AbstractViewHelper
{
    /**
     * Disable escaping of tag based ViewHelpers so that the rendered tag is not htmlspecialchar'd
     *
     * @var boolean
     */
    protected $escapeOutput = false;

    /**
     * Names of all registered tag attributes
     *
     * @var array
     */
    private static $tagAttributes = [];

    /**
     * Tag builder instance
     *
     * @var TagBuilder
     * @api
     */
    protected $tag = null;

    /**
     * Name of the tag to be created by this view helper
     *
     * @var string
     * @api
     */
    protected $tagName = 'div';

    /**
     * Constructor
     *
     * @api
     */
    public function __construct()
    {
        $this->registerArgument('additionalAttributes', 'array', 'Additional tag attributes. They will be added directly to the resulting HTML tag.', false);
        $this->registerArgument('data', 'array', 'Additional data-* attributes. They will each be added with a "data-" prefix.', false);
        $this->tag = new TagBuilder($this->tagName);
    }

    /**
     * @param TagBuilder $tag
     * @return void
     */
    public function injectTagBuilder(TagBuilder $tag)
    {
        $this->tag = $tag;
        $this->tag->setTagName($this->tagName);
    }

    /**
     * Sets the tag name to $this->tagName.
     * Additionally, sets all tag attributes which were registered in
     * $this->tagAttributes and additionalArguments.
     *
     * Will be invoked just before the render method.
     *
     * @return void
     * @api
     */
    public function initialize()
    {
        parent::initialize();
        $this->tag->reset();
        $this->tag->setTagName($this->tagName);
        if ($this->hasArgument('additionalAttributes') && is_array($this->arguments['additionalAttributes'])) {
            $this->tag->addAttributes($this->arguments['additionalAttributes']);
        }

        if ($this->hasArgument('data') && is_array($this->arguments['data'])) {
            foreach ($this->arguments['data'] as $dataAttributeKey => $dataAttributeValue) {
                $this->tag->addAttribute('data-' . $dataAttributeKey, $dataAttributeValue);
            }
        }

        if (isset(self::$tagAttributes[get_class($this)])) {
            foreach (self::$tagAttributes[get_class($this)] as $attributeName) {
                if ($this->hasArgument($attributeName) && $this->arguments[$attributeName] !== '' && $this->arguments[$attributeName] !== false) {
                    if ($this->arguments[$attributeName] === true) {
                        $this->tag->addAttribute($attributeName, '');
                        continue;
                    }
                    $this->tag->addAttribute($attributeName, $this->arguments[$attributeName]);
                }
            }
        }
    }

    /**
     * Register a new tag attribute. Tag attributes are all arguments which will be directly appended to a tag if you call $this->initializeTag()
     *
     * @param string $name Name of tag attribute
     * @param string $type Type of the tag attribute
     * @param string $description Description of tag attribute
     * @param boolean $required set to true if tag attribute is required. Defaults to false.
     * @param mixed $defaultValue Optional, default value of attribute if one applies
     * @return void
     * @api
     */
    protected function registerTagAttribute($name, $type, $description, $required = false, $defaultValue = null)
    {
        $this->registerArgument($name, $type, $description, $required, $defaultValue);
        self::$tagAttributes[get_class($this)][$name] = $name;
    }

    /**
     * Registers all standard HTML universal attributes.
     * Should be used inside registerArguments();
     *
     * @return void
     * @api
     */
    protected function registerUniversalTagAttributes()
    {
        $this->registerTagAttribute('class', 'string', 'CSS class(es) for this element');
        $this->registerTagAttribute('dir', 'string', 'Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)');
        $this->registerTagAttribute('id', 'string', 'Unique (in this file) identifier for this HTML element.');
        $this->registerTagAttribute('lang', 'string', 'Language for this element. Use short names specified in RFC 1766');
        $this->registerTagAttribute('style', 'string', 'Individual CSS styles for this element');
        $this->registerTagAttribute('title', 'string', 'Tooltip text of element');
        $this->registerTagAttribute('accesskey', 'string', 'Keyboard shortcut to access this element');
        $this->registerTagAttribute('tabindex', 'integer', 'Specifies the tab order of this element');
        $this->registerTagAttribute('onclick', 'string', 'JavaScript evaluated for the onclick event');
    }

    /**
     * Handles additional arguments, sorting out any data-
     * prefixed tag attributes and assigning them. Then passes
     * the unassigned arguments to the parent class' method,
     * which in the default implementation will throw an error
     * about "undeclared argument used".
     *
     * @param array $arguments
     * @return void
     */
    public function handleAdditionalArguments(array $arguments)
    {
        $unassigned = [];
        foreach ($arguments as $argumentName => $argumentValue) {
            if (strpos($argumentName, 'data-') === 0) {
                $this->tag->addAttribute($argumentName, $argumentValue);
            } else {
                $unassigned[$argumentName] = $argumentValue;
            }
        }
        parent::handleAdditionalArguments($unassigned);
    }
}
