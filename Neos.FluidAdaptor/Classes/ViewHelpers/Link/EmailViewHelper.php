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
 * you may optionally add a subject and a body to the VH
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
        $this->registerArgument('email', 'string', 'The email address to be turned into a link.', true);
        $this->registerArgument('subject', 'string', 'The subject of the email link.');
        $this->registerArgument('body', 'string', 'The body of the email link.');
    }

    /**
     * @return string Rendered email link
     * @api
     */
    public function render()
    {
        $email = $this->arguments['email'];

        $linkHref = $this->getMailtoLink(
            [$email],
            $this->arguments['subject'] ?? '',
            $this->arguments['body'] ?? ''
        );

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

    protected function getMailtoLink(array $recipients, string $subject, string $body)
    {
        $recipientsString = implode( ',', $recipients);
        $link = 'mailto:' . rawurldecode($recipientsString) . '?';
        if ($subject !== '') {
            $link .= 'subject=' . rawurlencode($subject);
        }
        if ($body !== '') {
            $link .= '&body=' . rawurlencode($body);
        }
        return $link;
    }
}
