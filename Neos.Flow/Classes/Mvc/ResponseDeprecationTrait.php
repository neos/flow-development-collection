<?php
namespace Neos\Flow\Mvc;

use Neos\Flow\Http\Cookie;
use Neos\Flow\Http\Headers;
use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Psr\Http\Message\StreamInterface;

/**
 * This is merely a shim to allow the Http\Response still have their PSR-7 methods non deprecated
 * while at the same time having all old methods deprecated in places where in future only an
 * ActionResponse is permitted, while maintaining backwards compatibility by having ActionResponse
 * extend the Http\Response.
 * We could have deprecated those methods in the ActionResponse, but that would pollute the class
 * with a lot of code that gets removed in the next major again. This way you can easily read
 * the signature of the ActionResponse and we can throw away this shim for the next major.
 *
 *
 * @deprecated since Flow 5.3, to be removed in 6.0
 * @see ActionResponse
 * @see Response
 */
trait ResponseDeprecationTrait
{
    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getHeaders()
    {
        return parent::getHeaders();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getHeader($name)
    {
        return parent::getHeader($name);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function hasHeader($name)
    {
        return parent::hasHeader($name);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function setHeader($name, $values, $replaceExistingHeader = true)
    {
        return parent::setHeader($name, $values, $replaceExistingHeader);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function setContent($content)
    {
        return parent::setContent($content);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function setCharset($charset)
    {
        return parent::setCharset($charset);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getCharset()
    {
        return parent::getCharset();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function setVersion($version)
    {
        parent::setVersion($version);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getVersion()
    {
        return parent::getVersion();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function setCookie(Cookie $cookie)
    {
        parent::setCookie($cookie);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getCookie($name)
    {
        return parent::getCookie($name);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getCookies()
    {
        return parent::getCookies();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function hasCookie($name)
    {
        return parent::hasCookie($name);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function removeCookie($name)
    {
        parent::removeCookie($name);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getProtocolVersion()
    {
        return parent::getProtocolVersion();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function withProtocolVersion($version)
    {
        return parent::withProtocolVersion($version);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getHeaderLine($name)
    {
        return parent::getHeaderLine($name);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function withHeader($name, $value)
    {
        return parent::withHeader($name, $value);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function withAddedHeader($name, $value)
    {
        return parent::withAddedHeader($name, $value);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function withoutHeader($name)
    {
        return parent::withoutHeader($name);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getBody()
    {
        return parent::getBody();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function withBody(StreamInterface $body)
    {
        return parent::withBody($body);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public static function getStatusMessageByCode($statusCode)
    {
        parent::getStatusMessageByCode($statusCode);
    }

    /**
     * Purely internal implementation to support backwards compatibility.
     *
     * @inheritDoc
     * @internal
     * @deprecated
     * TODO: Can be removed when the ActionResponse no longer extends HTTP response.
     */
    public static function createFromRaw($rawResponse, Response $parentResponse = null)
    {
        /** @var Response $response */
        $response = ResponseInformationHelper::createFromRaw($rawResponse, self::class);
        $response->parentResponse = $parentResponse;

        return $response;
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getParentResponse()
    {
        return parent::getParentResponse();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function appendContent($content)
    {
        return parent::appendContent($content);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getContent()
    {
        return parent::getContent();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function setStatus($code, $message = null)
    {
        return parent::setStatus($code, $message);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getStatus()
    {
        return parent::getStatus();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getStatusCode()
    {
        return parent::getStatusCode();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function setHeaders(Headers $headers)
    {
        parent::setHeaders($headers);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function setNow(\DateTime $now)
    {
        parent::setNow($now);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function setDate($date)
    {
        return parent::setDate($date);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getDate()
    {
        return parent::getDate();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function setLastModified($date)
    {
        return parent::setLastModified($date);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getLastModified()
    {
        return parent::getLastModified();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function setExpires($date)
    {
        return parent::setExpires($date);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getExpires()
    {
        return parent::getExpires();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getAge()
    {
        return parent::getAge();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function setMaximumAge($age)
    {
        return parent::setMaximumAge($age);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getMaximumAge()
    {
        return parent::getMaximumAge();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function setSharedMaximumAge($maximumAge)
    {
        return parent::setSharedMaximumAge($maximumAge);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getSharedMaximumAge()
    {
        return parent::getSharedMaximumAge();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function renderHeaders()
    {
        return parent::renderHeaders();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function setPublic()
    {
        return parent::setPublic();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function setPrivate()
    {
        return parent::setPrivate();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function makeStandardsCompliant(Request $request)
    {
        parent::makeStandardsCompliant($request);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function sendHeaders()
    {
        return parent::sendHeaders();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function send()
    {
        parent::send();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getStatusLine()
    {
        return parent::getStatusLine();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getStartLine()
    {
        return parent::getStartLine();
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        return parent::withStatus($code, $reasonPhrase);
    }

    /**
     * @inheritDoc
     * @deprecated since Flow 5.3, to be removed in 6.0
     * @internal
     */
    public function getReasonPhrase()
    {
        return parent::getReasonPhrase();
    }
}
