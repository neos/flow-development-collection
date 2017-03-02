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
use Neos\Flow\Http\Response;

/**
 * The HTTP component chain
 *
 * The chain is a HTTP component itself and handles all the configured components until one
 * component sets the "cancelled" flag.
 */
class ComponentChain implements ComponentInterface
{
    /**
     * Configurable options of the component chain, it mainly contains the "components" to handle
     *
     * @var array
     */
    protected $options;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Handle the configured components in the order of the chain
     *
     * @param ComponentContext $componentContext
     * @return void
     */
    public function handle(ComponentContext $componentContext)
    {
        if (!isset($this->options['components'])) {
            return;
        }
        /** @var ComponentInterface $component */
        foreach ($this->options['components'] as $component) {
            if ($component === null) {
                continue;
            }
            $component->handle($componentContext);
            $this->response = $componentContext->getHttpResponse();
            if ($componentContext->getParameter(ComponentChain::class, 'cancel') === true) {
                $componentContext->setParameter(ComponentChain::class, 'cancel', null);
                return;
            }
        }
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
