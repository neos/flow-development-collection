<?php
namespace Neos\Flow\Mvc;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\ComponentInterface;
use Neos\Flow\Mvc\Routing\RoutingComponent;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Context;

/**
 *
 */
class PrepareMvcRequestComponent implements ComponentInterface
{
    use ActionRequestFromHttpTrait;

    /**
     * @Flow\Inject(lazy=false)
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject(lazy=false)
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @inheritDoc
     */
    public function handle(ComponentContext $componentContext)
    {
        $httpRequest = $componentContext->getHttpRequest();

        $routingMatchResults = $componentContext->getParameter(RoutingComponent::class, 'matchResults');
        $actionRequest = $this->createActionRequest($httpRequest, $routingMatchResults ?? []);
        $this->securityContext->setRequest($actionRequest);
        $componentContext->setParameter(DispatchComponent::class, 'actionRequest', $actionRequest);

        return $componentContext;
    }
}
