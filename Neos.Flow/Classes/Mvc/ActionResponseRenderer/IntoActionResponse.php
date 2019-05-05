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
        $this->actionResponse->setContent($this->content);
        $this->actionResponse->setContentType($this->contentType);
        $this->actionResponse->setStatusCode($this->statusCode);
        $this->actionResponse->setRedirectUri($this->redirectUri);
        $this->actionResponse->setRedirectUri($this->redirectUri);

        return $this->actionResponse;
    }
}
