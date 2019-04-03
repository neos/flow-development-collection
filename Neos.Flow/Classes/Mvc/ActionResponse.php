<?php
namespace Neos\Flow\Mvc;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\AbstractMessage;
use Psr\Http\Message\UriInterface;

/**
 * The minimal MVC response object.
 * It allows for simple interactions with the HTTP response from within MVC actions. More specific requirements can be implemented via HTTP Components.
 * @see setComponentParameter()
 *
 * @Flow\Proxy(false)
 * @api
 */
final class ActionResponse extends \Neos\Flow\Http\Response
{
    use ResponseDeprecationTrait;

    /**
     * @var array
     */
    private $componentParameters = [];

    /**
     * @var UriInterface
     */
    private $redirectUri;

    /**
     * @var string
     */
    private $contentType = '';

    /**
     * @param string $content
     * @return void
     * @api
     */
    public function setContent($content): void
    {
        $this->content = $content;
    }

    /**
     * Set content mime type for this response.
     *
     * @param string $contentType
     * @return void
     * @api
     */
    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
        // TODO: This can be removed after the full changes are done for next major.
        $this->setHeader('Content-Type', $contentType, true);
    }

    /**
     * Set a redirect URI and according status for this response.
     *
     * @param UriInterface $uri
     * @param int $statusCode
     * @return void
     * @api
     */
    public function setRedirectUri(UriInterface $uri, int $statusCode = 303): void
    {
        $this->redirectUri = $uri;
        $this->statusCode = $statusCode;
        // TODO: This can be removed after the full changes are done for next major.
        $this->setHeader('Location', (string)$uri, true);
        $this->setStatusCode($statusCode);
    }

    /**
     * Set the status code for this response as HTTP status code.
     * Other codes than HTTP status may end in unpredictable results.
     *
     * @param int $statusCode
     * @return void
     * @api
     */
    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    /**
     * Sets the specified HTTP header
     *
     * In the next major this will
     *
     * Please note that dates are normalized to GMT internally, so that getHeader() will return
     * the same point in time, but not necessarily in the same timezone, if it was not
     * GMT previously. GMT is used synonymously with UTC as per RFC 2616 3.3.1.
     *
     * @param string $name Name of the header, for example "Location", "Content-Description" etc.
     * @param string|string[]|\DateTime $values An array of values or a single value for the specified header field
     * @param boolean $replaceExistingHeader If a header with the same name should be replaced. Default is true.
     * @return void
     * @throws \InvalidArgumentException
     * @see withHeader()
     * @see withAddedHeader()
     */
    public function setHeader($name, $values, $replaceExistingHeader = true): void
    {
        switch ($name) {
            case 'Content-Type':
                if (is_array($values)) {
                    if (count($values) !== 1) {
                        throw new \InvalidArgumentException('The "Content-Type" header must be unique and thus only one field value may be specified.', 1454949291);
                    }
                    $values = (string)$values[0];
                }
                if (stripos($values, 'charset') === false && stripos($values, 'text/') === 0) {
                    $values .= '; charset=UTF-8';
                }
                break;
        }

        $this->headers->set($name, $values, $replaceExistingHeader);
    }

    /**
     * Set a (HTTP) component parameter for use later in the chain.
     * This can be used to adjust all aspects of the later processing if needed.
     *
     * @param string $componentClassName
     * @param string $parameterName
     * @param mixed $value
     * @return void
     * @api
     */
    public function setComponentParameter(string $componentClassName, string $parameterName, $value): void
    {
        if (!isset($this->componentParameters[$componentClassName])) {
            $this->componentParameters[$componentClassName] = [];
        }
        $this->componentParameters[$componentClassName][$parameterName] = $value;
    }
}
