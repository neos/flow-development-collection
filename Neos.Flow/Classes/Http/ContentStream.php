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
use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/**
 * Implementation of a PSR-7 HTTP stream
 *
 * @api PSR-7
 */
class ContentStream implements StreamInterface
{
    /**
     * @var resource
     */
    protected $resource;

    /**
     * @var string|resource
     */
    protected $stream;

    /**
     * @Flow\Inject(name="Neos.Flow:SystemLogger")
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param string|resource $stream a valid PHP resource or a filename supported by fopen (see http://php.net/manual/en/function.fopen.php)
     * @param string $mode Mode with which to open stream
     * @throws \InvalidArgumentException
     */
    public function __construct($stream, $mode = 'r')
    {
        $this->replace($stream, $mode);
    }

    /**
     * Creates an instance representing the given $contents string
     *
     * @param string $contents
     * @return self
     */
    public static function fromContents(string $contents): self
    {
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $contents);
        rewind($handle);
        return new static($handle);
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        if (!$this->resource) {
            return;
        }

        $resource = $this->detach();
        fclose($resource);
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;

        return $resource;
    }

    /**
     * Attach a new stream/resource to the instance.
     *
     * @param string|resource $stream
     * @param string $mode
     */
    public function replace($stream, $mode = 'r')
    {
        $this->close();
        if (!is_resource($stream)) {
            $stream = @fopen($stream, $mode);
        }

        if (!$this->isValidResource($stream)) {
            throw new \InvalidArgumentException('Invalid stream provided; must be a string or stream resource', 1453891861);
        }

        $this->resource = $stream;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if (!$this->isValidResource($this->resource)) {
            return null;
        }

        $stats = fstat($this->resource);

        return $stats['size'];
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        $this->ensureResourceOpen();

        $result = ftell($this->resource);
        if (!is_int($result)) {
            throw new \RuntimeException('Error occurred during tell operation', 1453892219);
        }

        return $result;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        if (!$this->isValidResource($this->resource)) {
            return true;
        }

        return feof($this->resource);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        if (!$this->isValidResource($this->resource)) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);

        return $meta['seekable'];
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @return bool
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        $this->ensureResourceOpen();

        if (!$this->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable', 1453892227);
        }

        $result = fseek($this->resource, $offset, $whence);

        if (0 !== $result) {
            throw new \RuntimeException('Error seeking within stream', 1453892231);
        }

        return true;
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    public function rewind()
    {
        return $this->seek(0);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        if (!$this->isValidResource($this->resource)) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];

        return (
            strstr($mode, 'x')
            || strstr($mode, 'w')
            || strstr($mode, 'c')
            || strstr($mode, 'a')
            || strstr($mode, '+')
        );
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        if (!$this->isWritable()) {
            throw new \RuntimeException('Stream is not writable', 1453892241);
        }

        $result = fwrite($this->resource, $string);

        if (false === $result) {
            throw new \RuntimeException('Error writing to stream', 1453892247);
        }

        return $result;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        if (!$this->isValidResource($this->resource)) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];

        return (strstr($mode, 'r') || strstr($mode, '+'));
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length)
    {
        $this->ensureResourceReadable();

        $result = fread($this->resource, $length);

        if ($result === false) {
            throw new \RuntimeException('Error reading stream', 1453892260);
        }

        return $result;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents()
    {
        $this->ensureResourceReadable();

        $result = stream_get_contents($this->resource);
        if ($result === false) {
            throw new \RuntimeException('Error reading from stream', 1453892266);
        }

        return $result;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        if ($key === null) {
            return stream_get_meta_data($this->resource);
        }

        $metadata = stream_get_meta_data($this->resource);
        if (!array_key_exists($key, $metadata)) {
            return null;
        }

        return $metadata[$key];
    }

    /**
     * Throw an exception if the current resource is not readable.
     */
    protected function ensureResourceReadable()
    {
        if ($this->isReadable() === false) {
            throw new \RuntimeException('Stream is not readable.', 1453892039);
        }
    }

    /**
     * Throw an exception if the current resource is not valid.
     */
    protected function ensureResourceOpen()
    {
        if (!$this->isValidResource($this->resource)) {
            throw new \RuntimeException('No resource available to apply operation', 1453891806);
        }
    }

    /**
     * @return boolean
     */
    protected function isValidResource($resource)
    {
        return (is_resource($resource) && get_resource_type($resource) === 'stream');
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        if (!$this->isReadable()) {
            return '';
        }

        try {
            $this->rewind();

            return $this->getContents();
        } catch (\Exception $e) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->error(sprintf('Tried to convert a http content stream to a string but an exception occured: [%s] - %s', $e->getCode(), $e->getMessage()), ['exception' => $e] + LogEnvironment::fromMethodName(__METHOD__));
            }
            return '';
        }
    }
}
