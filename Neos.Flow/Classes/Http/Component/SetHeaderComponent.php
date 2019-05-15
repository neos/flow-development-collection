<?php
namespace Neos\Flow\Http\Component;

/**
 *
 */
class SetHeaderComponent implements ComponentInterface
{
    /**
     * @inheritDoc
     */
    public function handle(ComponentContext $componentContext)
    {
        $httpResponse = $componentContext->getHttpResponse();
        $parameters = $componentContext->getAllParametersFor(SetHeaderComponent::class);
        foreach ($parameters as $headerName => $headerValue) {
            $httpResponse = $httpResponse->withHeader($headerName, $headerValue);
        }

        $componentContext->replaceHttpResponse($httpResponse);
    }
}
