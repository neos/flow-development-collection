<?php
namespace Neos\Flow\Http;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * Container for HTTP header fields
 *
 * @deprecated Headers will be only accessed via request in the future, if this class stays, then as internal implementation detail.
 * @Flow\Proxy(false)
 * TODO: case-insensitive header name matching
 */
class Headers implements \Iterator
{
    /**
     * @var array
     */
    protected $fields = ['Cache-Control' => []];

    /**
     * @var array
     */
    protected $cookies = [];

    /**
     * @var array
     */
    protected $cacheDirectives = [
        'visibility' => '',
        'max-age' => '',
        's-maxage' => '',
        'must-revalidate' => '',
        'proxy-revalidate' => '',
        'no-store' => '',
        'no-transform' => ''
    ];

    /**
     * Constructs a new Headers object.
     *
     * @param array $fields Field names and their values (either as single value or array of values)
     */
    public function __construct(array $fields = [])
    {
        foreach ($fields as $name => $values) {
            $this->set($name, $values);
        }
    }

    /**
     * Creates a new Headers instance from the given $_SERVER-superglobal-like array.
     *
     * @param array $server An array similar or equal to $_SERVER, containing headers in the form of "HTTP_FOO_BAR"
     * @return Headers
     */
    public static function createFromServer(array $server)
    {
        $headerFields = [];
        if (isset($server['PHP_AUTH_USER']) && isset($server['PHP_AUTH_PW'])) {
            $headerFields['Authorization'] = 'Basic ' . base64_encode($server['PHP_AUTH_USER'] . ':' . $server['PHP_AUTH_PW']);
        }

        foreach ($server as $name => $value) {
            if (strpos($name, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headerFields[$name] = $value;
            } elseif ($name == 'REDIRECT_REMOTE_AUTHORIZATION' && !isset($headerFields['Authorization'])) {
                $headerFields['Authorization'] = $value;
            } elseif (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))));
                $headerFields[$name] = $value;
            }
        }
        return new self($headerFields);
    }

    /**
     * Sets the specified HTTP header
     *
     * DateTime objects will be converted to a string representation internally but
     * will be returned as DateTime objects on calling get().
     *
     * Please note that dates are normalized to GMT internally, so that get() will return
     * the same point in time, but not necessarily in the same timezone, if it was not
     * GMT previously. GMT is used synonymously with UTC as per RFC 2616 3.3.1.
     *
     * @param string $name Name of the header, for example "Location", "Content-Description" etc.
     * @param array|string|\DateTime $values An array of values or a single value for the specified header field
     * @param boolean $replaceExistingHeader If a header with the same name should be replaced. Default is true.
     * @return void
     * @throws \InvalidArgumentException
     * @api
     */
    public function set($name, $values, $replaceExistingHeader = true)
    {
        if ($values instanceof \DateTimeInterface) {
            $date = clone $values;
            $date->setTimezone(new \DateTimeZone('GMT'));
            $values = [$date->format('D, d M Y H:i:s') . ' GMT'];
        } else {
            $values = (array) $values;
        }

        switch ($name) {
            case 'Host':
                if (count($values) > 1) {
                    throw new \InvalidArgumentException('The "Host" header must be unique and thus only one field value may be specified.', 1478206019);
                }
                // Ensure Host is the first header.
                // See: http://tools.ietf.org/html/rfc7230#section-5.4
                $this->fields = ['Host' => $values] + $this->fields;
            break;
            case 'Cache-Control':
                $this->setCacheControlDirectivesFromRawHeader(implode(', ', $values));
            break;
            case 'Cookie':
                if (count($values) !== 1) {
                    throw new \InvalidArgumentException('The "Cookie" header must be unique and thus only one field value may be specified.', 1345127727);
                }
                $this->setCookiesFromRawHeader(array_pop($values));
            break;
            default:
                if ($replaceExistingHeader === true || !isset($this->fields[$name])) {
                    $this->fields[$name] = $values;
                } else {
                    $this->fields[$name] = array_merge($this->fields[$name], $values);
                }
        }
    }

    /**
     * Get raw header values
     *
     * @param string $name
     * @return string[]
     */
    public function getRaw(string $name): array
    {
        if (strtolower($name) === 'cache-control') {
            return $this->getCacheControlDirectives();
        }

        if (strtolower($name) === 'set-cookie') {
            $cookies = $this->fields['Set-Cookie'] ?? [];
            foreach ($this->cookies as $cookie) {
                $cookies[] = (string)$cookie;
            }

            return $cookies;
        }
        if (!isset($this->fields[$name])) {
            return [];
        }

        return $this->fields[$name];
    }

    /**
     * Returns the specified HTTP header
     *
     * Dates are returned as DateTime objects with the timezone set to GMT.
     *
     * @param string $name Name of the header, for example "Location", "Content-Description" etc.
     * @return array|string An array of field values if multiple headers of that name exist, a string value if only one value exists and NULL if there is no such header.
     * @api
     */
    public function get($name)
    {
        if (strtolower($name) === 'cache-control') {
            return $this->getCacheControlHeader();
        }

        if (strtolower($name) === 'set-cookie') {
            $cookies = $this->fields['Set-Cookie'] ?? [];
            foreach ($this->cookies as $cookie) {
                $cookies[] = (string)$cookie;
            }

            return $cookies;
        }

        if (!isset($this->fields[$name])) {
            return null;
        }

        $convertedValues = [];
        foreach ($this->fields[$name] as $index => $value) {
            $convertedValues[$index] = \DateTime::createFromFormat(DATE_RFC2822, $value);
            if ($convertedValues[$index] === false) {
                $convertedValues[$index] = $value;
            }
        }

        return (count($convertedValues) > 1) ? $convertedValues : reset($convertedValues);
    }

    /**
     * Returns all header fields
     *
     * Note that even for those header fields which exist only one time, the value is
     * returned as an array (with a single item).
     *
     * @return array
     * @api
     */
    public function getAll()
    {
        $fields = $this->fields;
        $cacheControlHeader = $this->getCacheControlHeader();
        $fields['Cache-Control'] = [$cacheControlHeader];
        if (empty($cacheControlHeader)) {
            unset($fields['Cache-Control']);
        }
        return $fields;
    }

    /**
     * Checks if the specified HTTP header exists
     *
     * @param string $name Name of the header
     * @return boolean
     * @api
     */
    public function has($name)
    {
        if ($name === 'Cache-Control') {
            return ($this->getCacheControlHeader() !== null);
        }
        return isset($this->fields[$name]);
    }

    /**
     * Removes the specified header field
     *
     * @param string $name Name of the field
     * @return void
     * @api
     */
    public function remove($name)
    {
        unset($this->fields[$name]);
    }

    /**
     * Sets a cookie
     *
     * @param Cookie $cookie
     * @return void
     * @api
     */
    public function setCookie(Cookie $cookie)
    {
        $this->cookies[$cookie->getName()] = $cookie;
    }

    /**
     * Returns a cookie specified by the given name
     *
     * @param string $name Name of the cookie
     * @return Cookie The cookie or NULL if no such cookie exists
     * @api
     */
    public function getCookie($name)
    {
        return isset($this->cookies[$name]) ? $this->cookies[$name] : null;
    }

    /**
     * Returns all cookies
     *
     * @return Cookie[]
     * @api
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Checks if the specified cookie exists
     *
     * @param string $name Name of the cookie
     * @return boolean
     * @api
     */
    public function hasCookie($name)
    {
        return isset($this->cookies[$name]);
    }

    /**
     * Removes the specified cookie if it exists
     *
     * Note: This will remove the cookie object from this Headers container. If you
     *       intend to remove a cookie in the user agent (browser), you should call
     *       the cookie's expire() method and _not_ remove the cookie from the Headers
     *       container.
     *
     * @param string $name Name of the cookie to remove
     * @return void
     * @api
     */
    public function removeCookie($name)
    {
        unset($this->cookies[$name]);
    }

    /**
     * Although not 100% semantically correct, an alias for removeCookie()
     *
     * @param string $name Name of the cookie to eat
     * @return void
     * @api
     */
    public function eatCookie($name)
    {
        $this->removeCookie($name);
    }

    /**
     * Sets a special directive for use in the Cache-Control header, according to
     * RFC 2616 / 14.9
     *
     * @param string $name Name of the directive, for example "max-age"
     * @param string $value An optional value
     * @return void
     * @api
     */
    public function setCacheControlDirective($name, $value = null)
    {
        switch ($name) {
            case 'public':
                $this->cacheDirectives['visibility'] = 'public';
            break;
            case 'private':
            case 'no-cache':
                $this->cacheDirectives['visibility'] = $name . (!empty($value) ? '="' . $value . '"' : '');
            break;
            case 'no-store':
            case 'no-transform':
            case 'must-revalidate':
            case 'proxy-revalidate':
                $this->cacheDirectives[$name] = $name;
            break;
            case 'max-age':
            case 's-maxage':
                $this->cacheDirectives[$name] = $name . '=' . $value;
            break;
        }
    }

    /**
     * Removes a special directive previously set for the Cache-Control header.
     *
     * @param string $name Name of the directive, for example "public"
     * @return void
     */
    public function removeCacheControlDirective($name)
    {
        switch ($name) {
            case 'public':
            case 'private':
            case 'no-cache':
                $this->cacheDirectives['visibility'] = '';
            break;
            case 'no-store':
            case 'max-age':
            case 's-maxage':
            case 'no-transform':
            case 'must-revalidate':
            case 'proxy-revalidate':
                $this->cacheDirectives[$name] = '';
            break;
        }
    }

    /**
     * Returns the value of the specified Cache-Control directive.
     *
     * If the cache directive is not present, NULL is returned. If the specified
     * directive is present but contains no value, this method returns true. Finally,
     * if the directive is present and does contain a value, the value is returned.
     *
     * @param string $name Name of the cache directive, for example "max-age"
     * @return mixed
     * @api
     */
    public function getCacheControlDirective($name)
    {
        $value = null;

        switch ($name) {
            case 'public':
                $value = ($this->cacheDirectives['visibility'] === 'public' ? true : null);
            break;
            case 'private':
            case 'no-cache':
                preg_match('/^(' . $name . ')(?:="([^"]+)")?$/', $this->cacheDirectives['visibility'], $matches);
                if (!isset($matches[1])) {
                    $value = null;
                } else {
                    $value = (isset($matches[2]) ? $matches[2] : true);
                }
            break;
            case 'no-store':
            case 'no-transform':
            case 'must-revalidate':
            case 'proxy-revalidate':
                $value = ($this->cacheDirectives[$name] !== '' ? true : null);
            break;
            case 'max-age':
            case 's-maxage':
                preg_match('/^(' . $name . ')=(.+)$/', $this->cacheDirectives[$name], $matches);
                if (!isset($matches[1])) {
                    $value = null;
                } else {
                    $value = (isset($matches[2]) ? intval($matches[2]) : true);
                }
            break;
        }

        return $value;
    }

    /**
     * Internally sets the cache directives correctly by parsing the given
     * Cache-Control field value.
     *
     * @param string $rawFieldValue The value of a specification compliant Cache-Control header
     * @return void
     * @see set()
     */
    protected function setCacheControlDirectivesFromRawHeader($rawFieldValue)
    {
        foreach (array_keys($this->cacheDirectives) as $key) {
            $this->cacheDirectives[$key] = '';
        }
        preg_match_all('/([a-zA-Z][a-zA-Z_-]*)\s*(?:=\s*(?:"([^"]*)"|([^,;\s"]*)))?/', $rawFieldValue, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if (isset($match[2]) && $match[2] !== '') {
                $value = $match[2];
            } elseif (isset($match[3]) && $match[3] !== '') {
                $value = $match[3];
            } else {
                $value = null;
            }
            $this->setCacheControlDirective(strtolower($match[1]), $value);
        }
    }

    /**
     * @return array
     */
    private function getCacheControlDirectives(): array
    {
        return array_values(array_filter($this->cacheDirectives));
    }

    /**
     * Renders and returns a Cache-Control header, based on the previously set
     * cache control directives.
     *
     * @return string Either the value of the header or NULL if it shall be omitted
     * @see get()
     */
    protected function getCacheControlHeader()
    {
        $cacheControl = '';
        foreach ($this->cacheDirectives as $cacheDirective) {
            $cacheControl .= ($cacheDirective !== '' ? $cacheDirective . ', ' : '');
        }
        $cacheControl = trim($cacheControl, ' ,');
        return ($cacheControl === '' ? null : $cacheControl);
    }

    /**
     * Internally sets cookie objects based on the Cookie header field value.
     *
     * @param string $rawFieldValue The value of a specification compliant Cookie header
     * @return void
     * @see set()
     */
    protected function setCookiesFromRawHeader($rawFieldValue)
    {
        $cookiePairs = explode(';', $rawFieldValue);
        foreach ($cookiePairs as $cookiePair) {
            if (strpos($cookiePair, '=') === false) {
                continue;
            }
            list($name, $value) = explode('=', $cookiePair, 2);
            $trimmedName = trim($name);

            if ($trimmedName !== '' && preg_match(Cookie::PATTERN_TOKEN, $trimmedName) === 1) {
                $this->setCookie(new Cookie($trimmedName, urldecode(trim($value, "\t ;\""))));
            }
        }
    }

    /**
     * Get all header lines prepared as "name: value" strings.
     *
     * @return array
     */
    public function getPreparedValues()
    {
        $preparedValues = [];
        foreach ($this->getAll() as $name => $values) {
            $preparedValues = array_merge($preparedValues, $this->prepareValues($name, $values));
        }

        return $preparedValues;
    }

    /**
     * @param string $headerName
     * @param array $values
     * @return array
     */
    private function prepareValues($headerName, array $values)
    {
        $preparedValues = [];
        foreach ($values as $value) {
            $preparedValues[] = sprintf("%s: %s", $headerName, $value);
        }

        return $preparedValues;
    }

    /**
     * @param string $headerName
     * @param array $values
     * @return string
     */
    private function renderValuesFor($headerName, array $values)
    {
        return implode("\r\n", $this->prepareValues($headerName, $values));
    }

    /**
     * Renders this headers object as string, with lines separated by "\r\n" as required by RFC 2616 sec 5.
     *
     * @return string
     * @api
     */
    public function __toString()
    {
        $headers = '';
        foreach ($this->getAll() as $name => $values) {
            $headers .= $this->renderValuesFor($name, $values) . "\r\n";
        }

        return $headers;
    }

    /**
     * @return string[]|mixed
     */
    public function current()
    {
        return $this->getRaw($this->key());
    }

    /**
     * @return void
     */
    public function next()
    {
        next($this->fields);
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return key($this->fields);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return !(key($this->fields) === null && current($this->fields) === false);
    }

    /**
     * @return void
     */
    public function rewind()
    {
        reset($this->fields);
    }
}
