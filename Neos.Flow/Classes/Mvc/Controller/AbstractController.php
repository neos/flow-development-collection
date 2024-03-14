<?php
namespace Neos\Flow\Mvc\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Utils;
use Neos\Error\Messages as Error;
use Neos\Flow\Annotations as Flow;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\Exception\InvalidActionNameException;
use Neos\Flow\Mvc\Exception\InvalidArgumentNameException;
use Neos\Flow\Mvc\Exception\InvalidArgumentTypeException;
use Neos\Flow\Mvc\Exception\InvalidControllerNameException;
use Neos\Flow\Mvc\Exception\NoSuchArgumentException;
use Neos\Flow\Mvc\FlashMessage\FlashMessageService;
use Neos\Flow\Mvc\Routing\Exception\MissingActionNameException;
use Neos\Flow\Persistence\Exception\UnknownObjectException;
use Neos\Flow\Property\Exception;
use Psr\Http\Message\UriInterface;
use Neos\Flow\Http\Helper\MediaTypeHelper;
use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\RequiredArgumentMissingException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Validation\ValidatorResolver;
use Neos\Utility\MediaTypes;

/**
 * An abstract base class for HTTP based controllers
 *
 * @api
 */
abstract class AbstractController implements ControllerInterface
{
    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @Flow\Inject
     * @var ValidatorResolver
     */
    protected $validatorResolver;

    /**
     * The current action request directed to this controller
     * @var ActionRequest
     * @api
     */
    protected $request;

    /**
     * The legacy response which will is provide by this action controller
     * @var ActionResponse
     * @deprecated with Flow 9 {@see ActionResponse}
     */
    protected $response;

    /**
     * Arguments passed to the controller
     * @var Arguments
     * @api
     */
    protected $arguments;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    #[Flow\Inject]
    protected FlashMessageService $flashMessageService;

    /**
     * A list of IANA media types which are supported by this controller
     *
     * @var array
     * @see http://www.iana.org/assignments/media-types/index.html
     */
    protected $supportedMediaTypes = ['text/html'];

    /**
     * The media type that was negotiated by this controller
     *
     * @var string
     */
    protected $negotiatedMediaType;

    /**
     * Initializes the controller
     *
     * This method should be called by the concrete processRequest() method.
     *
     * @param ActionRequest $request
     * @param ActionResponse $response
     */
    protected function initializeController(ActionRequest $request, ActionResponse $response)
    {
        // make the current request and response "globally" available to everywhere in this controller.
        $this->request = $request;
        $this->response = $response;

        $this->request->setDispatched(true);

        $this->uriBuilder = new UriBuilder();
        $this->uriBuilder->setRequest($request);

        $this->arguments = new Arguments([]);
        $this->controllerContext = new ControllerContext($request, $response, $this->arguments, $this->uriBuilder);

        $mediaType = MediaTypeHelper::negotiateMediaType(MediaTypeHelper::determineAcceptedMediaTypes($request->getHttpRequest()), $this->supportedMediaTypes);
        if ($mediaType === null) {
            $this->throwStatus(406);
        }
        $this->negotiatedMediaType = $mediaType;
        if ($request->getFormat() === '') {
            $request->setFormat(MediaTypes::getFilenameExtensionFromMediaType($mediaType));
        }
    }

    /**
     * Returns this controller's context.
     * Note that the context is only available after processRequest() has been called.
     *
     * @return ControllerContext The current controller context
     * @api
     */
    public function getControllerContext()
    {
        return $this->controllerContext;
    }

    /**
     * Creates a Message object and adds it to the FlashMessageContainer.
     *
     * This method should be used to add FlashMessages rather than interacting with the container directly.
     *
     * @param string $messageBody text of the FlashMessage
     * @param string $messageTitle optional header of the FlashMessage
     * @param string $severity severity of the FlashMessage (one of the Message::SEVERITY_* constants)
     * @param array $messageArguments arguments to be passed to the FlashMessage
     * @param integer $messageCode
     * @return void
     * @see Error\Message
     * @api
     */
    public function addFlashMessage(string $messageBody, string $messageTitle = '', string $severity = Error\Message::SEVERITY_OK, array $messageArguments = [], int $messageCode = null)
    {
        switch ($severity) {
            case Error\Message::SEVERITY_NOTICE:
                $message = new Error\Notice($messageBody, $messageCode, $messageArguments, $messageTitle);
                break;
            case Error\Message::SEVERITY_WARNING:
                $message = new Error\Warning($messageBody, $messageCode, $messageArguments, $messageTitle);
                break;
            case Error\Message::SEVERITY_ERROR:
                $message = new Error\Error($messageBody, $messageCode, $messageArguments, $messageTitle);
                break;
            default:
                $message = new Error\Message($messageBody, $messageCode, $messageArguments, $messageTitle);
                break;
        }
        $this->flashMessageService->getFlashMessageContainerForRequest($this->request)->addMessage($message);
    }

    /**
     * Forwards the request to another action and / or controller.
     *
     * Request is directly transferred to the other action / controller
     *
     * @param string $actionName Name of the action to forward to
     * @param string|null $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
     * @param string|null $packageKey Key of the package containing the controller to forward to. May also contain the sub package, concatenated with backslash (Vendor.Foo\Bar\Baz). If not specified, the current package is assumed.
     * @param array<string, mixed> $arguments Arguments to pass to the target action
     * @return never
     * @throws ForwardException
     * @throws InvalidActionNameException
     * @throws InvalidArgumentNameException
     * @throws InvalidArgumentTypeException
     * @throws InvalidControllerNameException
     * @throws UnknownObjectException
     * @see redirect()
     * @api
     */
    protected function forward(string $actionName, string $controllerName = null, string $packageKey = null, array $arguments = []): never
    {
        $nextRequest = clone $this->request;
        $nextRequest->setControllerActionName($actionName);

        if ($controllerName !== null) {
            $nextRequest->setControllerName($controllerName);
        }
        if ($packageKey !== null && str_contains($packageKey, '\\')) {
            [$packageKey, $subpackageKey] = explode('\\', $packageKey, 2);
        } else {
            $subpackageKey = null;
        }
        if ($packageKey !== null) {
            $nextRequest->setControllerPackageKey($packageKey);
            $nextRequest->setControllerSubpackageKey($subpackageKey);
        }

        $regularArguments = [];
        foreach ($arguments as $argumentName => $argumentValue) {
            if (str_starts_with($argumentName, '__')) {
                $nextRequest->setArgument($argumentName, $argumentValue);
            } else {
                $regularArguments[$argumentName] = $argumentValue;
            }
        }
        $nextRequest->setArguments($this->persistenceManager->convertObjectsToIdentityArrays($regularArguments));
        $this->arguments->removeAll();

        $this->forwardToRequest($nextRequest);
    }

    /**
     * Forwards the request to another action and / or controller.
     *
     * Request is directly transfered to the other action / controller
     *
     * @param ActionRequest $request The request to redirect to
     * @throws ForwardException
     * @see redirectToRequest()
     * @api
     */
    protected function forwardToRequest(ActionRequest $request): never
    {
        $nextRequest = clone $request;
        throw ForwardException::createForNextRequest($nextRequest, '');
    }

    /**
     * Redirects the request to another action and / or controller.
     *
     * Redirect will be sent to the client which then performs another request to the new URI.
     *
     * NOTE: This method only supports web requests and will throw an exception
     * if used with other request types.
     *
     * @param string $actionName Name of the action to forward to
     * @param string|null $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
     * @param string|null $packageKey Key of the package containing the controller to forward to. If not specified, the current package is assumed.
     * @param array<string, mixed> $arguments Array of arguments for the target action
     * @param integer $delay (optional) The delay in seconds. Default is no delay.
     * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
     * @param string|null $format The format to use for the redirect URI
     * @return never
     * @throws StopActionException
     * @throws \Neos\Flow\Http\Exception
     * @throws MissingActionNameException
     * @see forward()
     * @api
     */
    protected function redirect(string $actionName, ?string $controllerName = null, ?string $packageKey = null, array $arguments = [], int $delay = 0, int $statusCode = 303, string $format = null): never
    {
        if ($packageKey !== null && str_contains($packageKey, '\\') !== false) {
            [$packageKey, $subpackageKey] = explode('\\', $packageKey, 2);
        } else {
            $subpackageKey = null;
        }
        $this->uriBuilder->reset();
        if ($format === null) {
            $this->uriBuilder->setFormat($this->request->getFormat());
        } else {
            $this->uriBuilder->setFormat($format);
        }

        $uri = $this->uriBuilder->setCreateAbsoluteUri(true)->uriFor($actionName, $arguments, $controllerName, $packageKey, $subpackageKey);
        $this->redirectToUri($uri, $delay, $statusCode);
    }

    /**
     * Redirects the request to another action and / or controller.
     *
     * Redirect will be sent to the client which then performs another request to the new URI.
     *
     * @param ActionRequest $request The request to redirect to
     * @param integer $delay (optional) The delay in seconds. Default is no delay.
     * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
     * @return never
     * @throws MissingActionNameException
     * @throws StopActionException
     * @throws \Neos\Flow\Http\Exception
     * @see forwardToRequest()
     * @api
     */
    protected function redirectToRequest(ActionRequest $request, int $delay = 0, int $statusCode = 303): never
    {
        $packageKey = $request->getControllerPackageKey();
        $subpackageKey = $request->getControllerSubpackageKey();
        if ($subpackageKey !== null) {
            $packageKey .= '\\' . $subpackageKey;
        }
        $this->redirect($request->getControllerActionName(), $request->getControllerName(), $packageKey, $request->getArguments(), $delay, $statusCode, $request->getFormat());
    }

    /**
     * Redirects to another URI
     *
     * @param UriInterface|string $uri Either a string or a psr uri
     * @param integer $delay (optional) The delay in seconds. Default is no delay.
     * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
     * @throws StopActionException
     * @api
     */
    protected function redirectToUri(string|UriInterface $uri, int $delay = 0, int $statusCode = 303): never
    {
        $httpResponse = $this->response->buildHttpResponse();
        if ($delay === 0) {
            if (!$uri instanceof UriInterface) {
                $uri = new Uri($uri);
            }
            $httpResponse = $httpResponse
                ->withStatus($statusCode)
                ->withHeader('Location', (string)$uri);
        } else {
            $content = '<html><head><meta http-equiv="refresh" content="' . (int)$delay . ';url=' . $uri . '"/></head></html>';
            $httpResponse = $httpResponse
                ->withStatus($statusCode)
                ->withBody(Utils::streamFor($content));
        }
        throw StopActionException::createForResponse($httpResponse, '');
    }

    /**
     * Sends the specified HTTP status immediately.
     *
     * NOTE: This method only supports web requests and will throw an exception if used with other request types.
     *
     * @param integer $statusCode The HTTP status code
     * @param string $statusMessage A custom HTTP status message
     * @param string $content Body content which further explains the status
     * @throws StopActionException
     * @api
     */
    protected function throwStatus(int $statusCode, $statusMessage = null, $content = null): never
    {
        $httpResponse = $this->response->buildHttpResponse();
        $httpResponse = $httpResponse->withStatus($statusCode);
        if ($content === null) {
            $content = sprintf(
                '%s %s',
                $statusCode,
                $statusMessage ?? ResponseInformationHelper::getStatusMessageByCode($statusCode)
            );
        }
        $httpResponse = $httpResponse->withBody(Utils::streamFor($content));
        throw StopActionException::createForResponse($httpResponse, $content);
    }

    /**
     * Maps arguments delivered by the request object to the local controller arguments.
     *
     * @param ActionRequest $request
     * @return void
     * @throws RequiredArgumentMissingException
     * @throws NoSuchArgumentException
     * @throws Exception
     * @throws \Neos\Flow\Security\Exception
     * @api
     */
    protected function mapRequestArgumentsToControllerArguments(ActionRequest $request, Arguments $arguments)
    {
        /* @var $argument \Neos\Flow\Mvc\Controller\Argument */
        foreach ($arguments as $argument) {
            $argumentName = $argument->getName();
            if ($argument->getMapRequestBody()) {
                $argument->setValue($request->getHttpRequest()->getParsedBody());
            } elseif ($request->hasArgument($argumentName)) {
                $argument->setValue($request->getArgument($argumentName));
            } elseif ($argument->isRequired()) {
                throw new RequiredArgumentMissingException('Required argument "' . $argumentName  . '" is not set.', 1298012500);
            }
        }
    }
}
