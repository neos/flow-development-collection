<?php
namespace Neos\Flow\Mvc\ActionResponseRenderer;

use Neos\Flow\Mvc\ActionReponseRendererInterface;

/**
 *
 */
class ToArray implements ActionReponseRendererInterface
{
    use BasicSetterTrait;

    /**
     * @inheritDoc
     */
    public function render()
    {
        return [
            'statusCode' => $this->statusCode,
            'contentType' => $this->contentType,
            'content' => $this->content,
            'redirectUri' => $this->redirectUri,
            'componentParameters' => $this->componentParameters
        ];
    }
}
