<?php
namespace TYPO3\Flow\Http;

use Psr\Http\Message\StreamInterface;

/**
 * Implementation of a PSR-7 HTTP stream
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
     * @param string|resource $stream
     * @param string $mode Mode with which to open stream
     * @throws \InvalidArgumentException
     */
    public function __construct($stream, $mode = 'r')
    {
        $this->replace($stream, $mode);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
        $this->resource = $stream;
        if (!is_resource($stream)) {
            $this->openStream($stream, $mode);
        }

        if (!$this->hasValidResource()) {
            throw new \InvalidArgumentException('Invalid stream provided; must be a string or stream resource', 1453891861);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        if (!$this->hasValidResource()) {
            return null;
        }

        $stats = fstat($this->resource);

        return $stats['size'];
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function eof()
    {
        if (!$this->hasValidResource()) {
            return true;
        }

        return feof($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        if (!$this->hasValidResource()) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);

        return $meta['seekable'];
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function rewind()
    {
        return $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        if (!$this->hasValidResource()) {
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function isReadable()
    {
        if (!$this->hasValidResource()) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];

        return (strstr($mode, 'r') || strstr($mode, '+'));
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * Set the internal stream resource.
     *
     * @param string $stream String stream target or stream resource.
     * @param string $mode Resource mode for stream target.
     * @return void
     */
    protected function openStream($stream, $mode = 'r')
    {
        $resource = fopen($stream, $mode);
        $this->resource = $resource;
    }

    protected function ensureResourceReadable()
    {
        if ($this->isReadable() === false) {
            throw new \RuntimeException('Stream is not readable.', 1453892039);
        }
    }

    /**
     *
     */
    protected function ensureResourceOpen()
    {
        if (!$this->hasValidResource()) {
            throw new \RuntimeException('No resource available to apply operation', 1453891806);
        }
    }

    /**
     * @return boolean
     */
    protected function hasValidResource()
    {
        return (is_resource($this->resource) && get_resource_type($this->resource) === 'stream');
    }

    /**
     * {@inheritdoc}
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
            return '';
        }
    }
}
