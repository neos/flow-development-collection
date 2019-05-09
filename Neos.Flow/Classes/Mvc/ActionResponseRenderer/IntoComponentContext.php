<?php
namespace Neos\Flow\Mvc\ActionResponseRenderer;

use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Helper\ArgumentsHelper;
use Neos\Flow\Mvc\ActionReponseRendererInterface;

/**
 *
 */
class IntoComponentContext implements ActionReponseRendererInterface
{
    use BasicSetterTrait;

    /**
     * @var ComponentContext
     */
    private $componentContext;

    /**
     * IntoComponentContext constructor.
     *
     * @param ComponentContext $componentContext
     */
    public function __construct(ComponentContext $componentContext)
    {
        $this->componentContext = $componentContext;
    }

    /**
     * @return ComponentContext
     */
    public function render(): ComponentContext
    {
        $httpResponse = $this->componentContext->getHttpResponse();
        $httpResponse = $httpResponse
            ->withStatus($this->statusCode);

        if ($this->content !== null) {
            $httpResponse = $httpResponse->withBody(ArgumentsHelper::createContentStreamFromString($this->content));
        }


        if ($this->contentType) {
            $httpResponse = $httpResponse->withHeader('Content-Type', $this->contentType);
        }

        if ($this->redirectUri) {
            $httpResponse = $httpResponse->withHeader('Location', $this->redirectUri);
        }

        foreach ($this->componentParameters as $componentClassName => $componentParameterGroup) {
            foreach ($componentParameterGroup as $parameterName => $parameterValue) {
                $this->componentContext->setParameter($componentClassName, $parameterName, $parameterValue);
            }
        }

        $this->componentContext->replaceHttpResponse($httpResponse);
        return $this->componentContext;
    }
}
