<?php
namespace Neos\Flow\Mvc\ActionResponseRenderer;

use Neos\Flow\Mvc\ActionReponseRendererInterface;
use Neos\Flow\Mvc\ActionResponse;

/**
 *
 */
class IntoActionResponse implements ActionReponseRendererInterface
{
    use BasicSetterTrait;

    /**
     * @var ActionResponse
     */
    private $actionResponse;

    /**
     * IntoActionResponse constructor.
     *
     * @param ActionResponse $actionResponse
     */
    public function __construct(ActionResponse $actionResponse)
    {
        $this->actionResponse = $actionResponse;
    }

    /**
     * @return ActionResponse
     */
    public function render(): ActionResponse
    {
        if (!empty($this->content)) {
            $this->actionResponse->setContent($this->content);
        }
        if ($this->contentType !== null) {
            $this->actionResponse->setContentType($this->contentType);
        }

        if ($this->statusCode !== null) {
            $this->actionResponse->setStatusCode($this->statusCode);
        }

        if ($this->redirectUri !== null) {
            $this->actionResponse->setRedirectUri($this->redirectUri);
        }

        foreach ($this->componentParameters as $componentClass => $parameters) {
            foreach ($parameters as $parameterName => $parameterValue) {
                $this->actionResponse->setComponentParameter($componentClass, $parameterName, $parameterValue);
            }
        }

        return $this->actionResponse;
    }
}
