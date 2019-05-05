<?php
namespace Neos\Flow\Mvc\ActionResponseRenderer;

use Neos\Flow\Mvc\ActionReponseRendererInterface;

/**
 *
 */
class Content implements ActionReponseRendererInterface
{
    use BasicSetterTrait;

    /**
     * @return mixed
     */
    public function render()
    {
        return $this->content;
    }
}
