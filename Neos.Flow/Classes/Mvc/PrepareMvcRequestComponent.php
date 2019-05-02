<?php
namespace Neos\Flow\Mvc;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\ComponentInterface;
use Neos\Flow\Http\Helper\ArgumentsHelper;
use Neos\Flow\Http\Helper\UploadedFilesHelper;
use Neos\Flow\Mvc\Routing\RoutingComponent;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Utility\Arrays;

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
        // TODO Move arguments merging into ActionRequest
        $arguments = ArgumentsHelper::buildUnifiedArguments($httpRequest->getQueryParams(), $httpRequest->getParsedBody(), UploadedFilesHelper::untangleFilesArray($_FILES));

        /** @var $actionRequest ActionRequest */
        $actionRequest = $this->objectManager->get(ActionRequest::class, $httpRequest);

        $routingMatchResults = $componentContext->getParameter(RoutingComponent::class, 'matchResults');
        if ($routingMatchResults !== null) {
            $arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $routingMatchResults);
        }

        $actionRequest->setArguments($arguments);
        $this->setDefaultControllerAndActionNameIfNoneSpecified($actionRequest);

        $this->securityContext->setRequest($actionRequest);
        $componentContext->replaceHttpRequest($httpRequest);

        $componentContext->setParameter(DispatchComponent::class, 'actionRequest', $actionRequest);

        return $componentContext;
    }

    /**
     * Set the default controller and action names if none has been specified.
     *
     * @param ActionRequest $actionRequest
     * @return void
     */
    protected function setDefaultControllerAndActionNameIfNoneSpecified(ActionRequest $actionRequest)
    {
        if ($actionRequest->getControllerName() === null) {
            $actionRequest->setControllerName('Standard');
        }
        if ($actionRequest->getControllerActionName() === null) {
            $actionRequest->setControllerActionName('index');
        }
    }
}
