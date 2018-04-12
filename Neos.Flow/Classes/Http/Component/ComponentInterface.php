<?php
namespace Neos\Flow\Http\Component;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * An HTTP component
 *
 * A component is one item of the configurable component chain that is processed for every incoming request. A component can change the current HTTP request and response,
 * can communicate with other components and even change the currently processed chain using the ComponentContext that gets passed to its handle() method.
 *
 * @api
 */
interface ComponentInterface
{
    /**
     * Constructs the component and sets options
     *
     * Note: Constructors must not be defined in PHP interfaces, but this should be implemented in custom component implementations
     *
     * @param array $options The component options
     * @api
     */
    // public function __construct(array $options = array());

    /**
     * @param ComponentContext $componentContext
     * @return void
     * @api
     */
    public function handle(ComponentContext $componentContext);
}
