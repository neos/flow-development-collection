<?php
namespace Neos\Flow\Mvc\FlashMessage;

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
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\ComponentInterface;

/**
 * A HTTP Component that persists any new FlashMessages that have been added during the current request cycle
 */
class FlashMessageComponent implements ComponentInterface
{

    /**
     * @Flow\Inject
     * @var FlashMessageService
     */
    protected $flashMessageService;

    /**
     * @param ComponentContext $componentContext
     * @return void
     */
    public function handle(ComponentContext $componentContext)
    {
        $response = $this->flashMessageService->persistFlashMessages($componentContext->getHttpResponse());
        $componentContext->replaceHttpResponse($response);
    }
}
