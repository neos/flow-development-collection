<?php
namespace Neos\Flow\Mvc;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\ComponentInterface;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Security\Context;

/**
 *
 */
class PrepareMvcRequestComponent implements ComponentInterface
{
    /**
     * @Flow\Inject(lazy=false)
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var ActionRequestFactory
     */
    protected $actionRequestFactory;

    /**
     * @inheritDoc
     */
    public function handle(ComponentContext $componentContext)
    {
        $httpRequest = $componentContext->getHttpRequest();

        $routingMatchResults = $httpRequest->getAttribute(ServerRequestAttributes::ROUTING_RESULTS) ?? [];
        $actionRequest = $this->actionRequestFactory->createActionRequest($httpRequest, $routingMatchResults);
        $this->securityContext->setRequest($actionRequest);
        $componentContext->setParameter(DispatchComponent::class, 'actionRequest', $actionRequest);
    }
}
