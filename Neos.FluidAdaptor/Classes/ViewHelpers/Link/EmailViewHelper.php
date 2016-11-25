<?php
namespace Neos\FluidAdaptor\ViewHelpers\Link;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Email link view helper.
 * Generates an email link.
 *
 * = Examples =
 *
 * <code title="basic email link">
 * <f:link.email email="foo@bar.tld" />
 * </code>
 * <output>
 * <a href="mailto:foo@bar.tld">foo@bar.tld</a>
 * </output>
 *
 * <code title="Email link with custom linktext">
 * <f:link.email email="foo@bar.tld">some custom content</f:link.email>
 * </code>
 * <output>
 * <a href="mailto:foo@bar.tld">some custom content</a>
 * </output>
 *
 * @api
 */
class EmailViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * Arguments initialization
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
        $this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked document');
        $this->registerTagAttribute('rev', 'string', 'Specifies the relationship between the linked document and the current document');
        $this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
    }

    /**
     * @param string $email The email address to be turned into a link.
     * @return string Rendered email link
     * @api
     */
    public function render($email)
    {
        $linkHref = 'mailto:' . $email;
        $linkText = $email;
        $tagContent = $this->renderChildren();
        if ($tagContent !== null) {
            $linkText = $tagContent;
        }
        $this->tag->setContent($linkText);
        $this->tag->addAttribute('href', $linkHref);
        $this->tag->forceClosingTag(true);

        return $this->tag->render();
    }
}
