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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper;

/**
 * This ViewHelper renders the identifier of a persisted object (if it has an identity).
 * Usually the identifier is the UUID of the object, but it could be an array of the
 * identity properties, too.
 * @see \Neos\Flow\Persistence\PersistenceManagerInterface::getIdentifierByObject()
 *
 * Useful for using the identifier outside of the form view helpers
 * (e.g. JavaScript and AJAX).
 *
 * = Examples =
 *
 * <code title="Inline notation">
 * {post.blog -> f:format.identifier()}
 * </code>
 * <output>
 * 97e7e90a-413c-44ef-b2d0-ddfa4387b5ca
 * // depending on {post.blog}
 * </output>
 *
 * <code title="JSON encoding">
 * <f:format.json>{identifier: '{someObject -> f:format.identifier()}'}</f:format.json>
 * </code>
 * <output>
 * {"identifier":"bf37f335-b273-4353-af77-fd8dc65cb66f"}
 * // depending on the UUID of {someObject}
 * </output>
 *
 * @api
 */
class IdentifierViewHelper extends AbstractViewHelper
{
    /**
     * @var boolean
     */
    protected $escapeChildren = false;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * Outputs the identifier of the specified object
     *
     * @param object $value the object to render the identifier for, or NULL if VH children should be used
     * @return mixed the identifier of $value, usually the UUID
     * @throws ViewHelper\Exception if the given value is no object
     * @api
     */
    public function render($value = null)
    {
        if ($value === null) {
            $value = $this->renderChildren();
        }
        if ($value === null) {
            return null;
        }
        if (!is_object($value)) {
            throw new ViewHelper\Exception('f:format.identifier expects an object, ' . gettype($value) . ' given.', 1337700024);
        }
        return $this->persistenceManager->getIdentifierByObject($value);
    }
}
