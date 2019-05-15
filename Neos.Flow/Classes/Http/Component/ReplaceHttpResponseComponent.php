<?php
namespace Neos\Flow\Http\Component;

use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class ReplaceHttpResponseComponent implements ComponentInterface
{
    CONST PARAMETER_RESPONSE = 'response';

    /**
     * @inheritDoc
     */
    public function handle(ComponentContext $componentContext)
    {
        $possibleResponse = $componentContext->getParameter(ReplaceHttpResponseComponent::class, ReplaceHttpResponseComponent::PARAMETER_RESPONSE);
        if (!$possibleResponse instanceof ResponseInterface) {
            return;
        }

        $componentContext->replaceHttpResponse($possibleResponse);
    }
}
