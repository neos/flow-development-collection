<?php
namespace TYPO3\Flow\Mvc\Controller;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\MediaTypes;

/**
 * An abstract base class for HTTP based controllers
 *
 * @api
 */
abstract class AbstractController implements ControllerInterface
{
    /**
     * @var \TYPO3\Flow\Mvc\Routing\UriBuilder
     */
    protected $uriBuilder;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Validation\ValidatorResolver
     */
    protected $validatorResolver;

    /**
     * The current action request directed to this controller
     * @var \TYPO3\Flow\Mvc\ActionRequest
     * @api
     */
    protected $request;

    /**
     * The response which will be returned by this action controller
     * @var \TYPO3\Flow\Http\Response
     * @api
     */
    protected $response;

    /**
     * Arguments passed to the controller
     * @var \TYPO3\Flow\Mvc\Controller\Arguments
     * @api
     */
    protected $arguments;

    /**
     * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * The flash messages. Use $this->flashMessageContainer->addMessage(...) to add a new Flash
     * Message.
     *
     * @Flow\Inject
     * @var \TYPO3\Flow\Mvc\FlashMessageContainer
     */
    protected $flashMessageContainer;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * A list of IANA media types which are supported by this controller
     *
     * @var array
     * @see http://www.iana.org/assignments/media-types/index.html
     */
    protected $supportedMediaTypes = array('text/html');

    /**
     * Initializes the controller
     *
     * This method should be called by the concrete processRequest() method.
     *
     * @param \TYPO3\Flow\Mvc\RequestInterface $request
     * @param \TYPO3\Flow\Mvc\ResponseInterface $response
     * @throws \TYPO3\Flow\Mvc\Exception\UnsupportedRequestTypeException
     */
    protected function initializeController(\TYPO3\Flow\Mvc\RequestInterface $request, \TYPO3\Flow\Mvc\ResponseInterface $response)
    {
        if (!$request instanceof ActionRequest) {
            throw new \TYPO3\Flow\Mvc\Exception\UnsupportedRequestTypeException(get_class($this) . ' only supports action requests – requests of type "' . get_class($request) . '" given.', 1187701131);
        }

        $this->request = $request;
        $this->request->setDispatched(true);
        $this->response = $response;

        $this->uriBuilder = new UriBuilder();
        $this->uriBuilder->setRequest($this->request);

        $this->arguments = new Arguments(array());
        $this->controllerContext = new ControllerContext($this->request, $this->response, $this->arguments, $this->uriBuilder);

        $mediaType = $request->getHttpRequest()->getNegotiatedMediaType($this->supportedMediaTypes);
        if ($mediaType === null) {
            $this->throwStatus(406);
        }
        if ($request->getFormat() === null) {
            $this->request->setFormat(MediaTypes::getFilenameExtensionFromMediaType($mediaType));
        }
    }

    /**
     * Returns this controller's context.
     * Note that the context is only available after processRequest() has been called.
     *
     * @return \TYPO3\Flow\Mvc\Controller\ControllerContext The current controller context
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
     * @param string $severity severity of the FlashMessage (one of the \TYPO3\Flow\Error\Message::SEVERITY_* constants)
     * @param array $messageArguments arguments to be passed to the FlashMessage
     * @param integer $messageCode
     * @return void
     * @throws \InvalidArgumentException if the message body is no string
     * @see \TYPO3\Flow\Error\Message
     * @api
     */
    public function addFlashMessage($messageBody, $messageTitle = '', $severity = Message::SEVERITY_OK, array $messageArguments = array(), $messageCode = null)
    {
        if (!is_string($messageBody)) {
            throw new \InvalidArgumentException('The message body must be of type string, "' . gettype($messageBody) . '" given.', 1243258395);
        }
        switch ($severity) {
            case Message::SEVERITY_NOTICE:
                $message = new \TYPO3\Flow\Error\Notice($messageBody, $messageCode, $messageArguments, $messageTitle);
                break;
            case Message::SEVERITY_WARNING:
                $message = new \TYPO3\Flow\Error\Warning($messageBody, $messageCode, $messageArguments, $messageTitle);
                break;
            case Message::SEVERITY_ERROR:
                $message = new \TYPO3\Flow\Error\Error($messageBody, $messageCode, $messageArguments, $messageTitle);
                break;
            default:
                $message = new Message($messageBody, $messageCode, $messageArguments, $messageTitle);
            break;
        }
        $this->flashMessageContainer->addMessage($message);
    }

    /**
     * Forwards the request to another action and / or controller.
     *
     * Request is directly transferred to the other action / controller
     *
     * @param string $actionName Name of the action to forward to
     * @param string $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
     * @param string $packageKey Key of the package containing the controller to forward to. May also contain the sub package, concatenated with backslash (Vendor.Foo\Bar\Baz). If not specified, the current package is assumed.
     * @param array $arguments Arguments to pass to the target action
     * @return void
     * @throws \TYPO3\Flow\Mvc\Exception\ForwardException
     * @see redirect()
     * @api
     */
    protected function forward($actionName, $controllerName = null, $packageKey = null, array $arguments = array())
    {
        $nextRequest = clone $this->request;
        $nextRequest->setControllerActionName($actionName);

        if ($controllerName !== null) {
            $nextRequest->setControllerName($controllerName);
        }
        if ($packageKey !== null && strpos($packageKey, '\\') !== false) {
            list($packageKey, $subpackageKey) = explode('\\', $packageKey, 2);
        } else {
            $subpackageKey = null;
        }
        if ($packageKey !== null) {
            $nextRequest->setControllerPackageKey($packageKey);
        }
        if ($subpackageKey !== null) {
            $nextRequest->setControllerSubpackageKey($subpackageKey);
        }

        $regularArguments = array();
        foreach ($arguments as $argumentName => $argumentValue) {
            if (substr($argumentName, 0, 2) === '__') {
                $nextRequest->setArgument($argumentName, $argumentValue);
            } else {
                $regularArguments[$argumentName] = $argumentValue;
            }
        }
        $nextRequest->setArguments($this->persistenceManager->convertObjectsToIdentityArrays($regularArguments));
        $this->arguments->removeAll();

        $forwardException = new \TYPO3\Flow\Mvc\Exception\ForwardException();
        $forwardException->setNextRequest($nextRequest);
        throw $forwardException;
    }

    /**
     * Forwards the request to another action and / or controller.
     *
     * Request is directly transfered to the other action / controller
     *
     * @param ActionRequest $request The request to redirect to
     * @return void
     * @throws \TYPO3\Flow\Mvc\Exception\ForwardException
     * @see redirectToRequest()
     * @api
     */
    protected function forwardToRequest(ActionRequest $request)
    {
        $packageKey = $request->getControllerPackageKey();
        $subpackageKey = $request->getControllerSubpackageKey();
        if ($subpackageKey !== null) {
            $packageKey .= '\\' . $subpackageKey;
        }
        $this->forward($request->getControllerActionName(), $request->getControllerName(), $packageKey, $request->getArguments());
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
     * @param string $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
     * @param string $packageKey Key of the package containing the controller to forward to. If not specified, the current package is assumed.
     * @param array $arguments Array of arguments for the target action
     * @param integer $delay (optional) The delay in seconds. Default is no delay.
     * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
     * @param string $format The format to use for the redirect URI
     * @return void
     * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
     * @see forward()
     * @api
     */
    protected function redirect($actionName, $controllerName = null, $packageKey = null, array $arguments = null, $delay = 0, $statusCode = 303, $format = null)
    {
        if ($packageKey !== null && strpos($packageKey, '\\') !== false) {
            list($packageKey, $subpackageKey) = explode('\\', $packageKey, 2);
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
     * NOTE: This method only supports web requests and will throw an exception
     * if used with other request types.
     *
     * @param ActionRequest $request The request to redirect to
     * @param integer $delay (optional) The delay in seconds. Default is no delay.
     * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
     * @return void
     * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
     * @see forwardToRequest()
     * @api
     */
    protected function redirectToRequest(ActionRequest $request, $delay = 0, $statusCode = 303)
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
     * @param mixed $uri Either a string representation of a URI or a \TYPO3\Flow\Http\Uri object
     * @param integer $delay (optional) The delay in seconds. Default is no delay.
     * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
     * @throws \TYPO3\Flow\Mvc\Exception\UnsupportedRequestTypeException If the request is not a web request
     * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
     * @api
     */
    protected function redirectToUri($uri, $delay = 0, $statusCode = 303)
    {
        $escapedUri = htmlentities($uri, ENT_QUOTES, 'utf-8');
        $this->response->setContent('<html><head><meta http-equiv="refresh" content="' . intval($delay) . ';url=' . $escapedUri . '"/></head></html>');
        $this->response->setStatus($statusCode);
        if ($delay === 0) {
            $this->response->setHeader('Location', (string)$uri);
        }
        throw new \TYPO3\Flow\Mvc\Exception\StopActionException();
    }

    /**
     * Sends the specified HTTP status immediately.
     *
     * NOTE: This method only supports web requests and will throw an exception if used with other request types.
     *
     * @param integer $statusCode The HTTP status code
     * @param string $statusMessage A custom HTTP status message
     * @param string $content Body content which further explains the status
     * @throws \TYPO3\Flow\Mvc\Exception\UnsupportedRequestTypeException If the request is not a web request
     * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
     * @api
     */
    protected function throwStatus($statusCode, $statusMessage = null, $content = null)
    {
        $this->response->setStatus($statusCode, $statusMessage);
        if ($content === null) {
            $content = $this->response->getStatus();
        }
        $this->response->setContent($content);
        throw new \TYPO3\Flow\Mvc\Exception\StopActionException();
    }

    /**
     * Maps arguments delivered by the request object to the local controller arguments.
     *
     * @return void
     * @throws \TYPO3\Flow\Mvc\Exception\RequiredArgumentMissingException
     * @api
     */
    protected function mapRequestArgumentsToControllerArguments()
    {
        foreach ($this->arguments as $argument) {
            $argumentName = $argument->getName();
            if ($this->request->hasArgument($argumentName)) {
                $argument->setValue($this->request->getArgument($argumentName));
            } elseif ($argument->isRequired()) {
                throw new \TYPO3\Flow\Mvc\Exception\RequiredArgumentMissingException('Required argument "' . $argumentName . '" is not set.', 1298012500);
            }
        }
    }
}
