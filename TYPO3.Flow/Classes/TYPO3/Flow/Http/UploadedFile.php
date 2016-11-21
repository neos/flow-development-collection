<?php
namespace TYPO3\Flow\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * Generic implementation of the PSR-7 UploadedFileInterface.
 *
 */
class UploadedFile implements UploadedFileInterface
{
    /**
     * @var string
     */
    protected $clientFilename;

    /**
     * @var string
     */
    protected $clientMediaType;

    /**
     * @var int
     */
    protected $error;

    /**
     * @var null|string
     */
    protected $file;

    /**
     * @var bool
     */
    protected $moved = false;

    /**
     * @var int
     */
    protected $size;

    /**
     * @var StreamInterface|null
     */
    protected $stream;

    /**
     * @param StreamInterface|string|resource $streamOrFile
     * @param int $size
     * @param int $errorStatus
     * @param string|null $clientFilename
     * @param string|null $clientMediaType
     */
    public function __construct($streamOrFile, $size, $errorStatus, $clientFilename = null, $clientMediaType = null) {
        $this->error = $errorStatus;
        $this->size = $size;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;

        if ($this->isOk()) {
            $this->setStreamOrFile($streamOrFile);
        }
    }

    /**
     * Depending on the value set file or stream variable
     *
     * @param mixed $streamOrFile
     * @throws InvalidArgumentException
     */
    protected function setStreamOrFile($streamOrFile)
    {
        if (is_string($streamOrFile)) {
            $this->file = $streamOrFile;
        } elseif (is_resource($streamOrFile)) {
            $this->stream = new ContentStream($streamOrFile);
        } elseif ($streamOrFile instanceof StreamInterface) {
            $this->stream = $streamOrFile;
        } else {
            throw new InvalidArgumentException(
                'Invalid stream or file provided for UploadedFile'
            );
        }
    }

    /**
     * Return true if there is no upload error
     *
     * @return boolean
     */
    protected function isOk()
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * @return boolean
     */
    public function isMoved()
    {
        return $this->moved;
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException if the upload was not successful.
     */
    public function getStream()
    {
        $this->throwExceptionIfNotAccessible();

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        return new ContentStream($this->file, 'r+');
    }

    /**
     * {@inheritdoc}
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     * @param string $targetPath Path to which to move the uploaded file.
     * @throws RuntimeException if the upload was not successful.
     * @throws InvalidArgumentException if the $path specified is invalid.
     * @throws RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    public function moveTo($targetPath)
    {
        $this->throwExceptionIfNotAccessible();

        if (!is_string($targetPath) || empty($targetPath)) {
            throw new InvalidArgumentException('Invalid path provided to move uploaded file to. Must be a non-empty string', 1479747624);
        }

        if ($this->stream !== null || ($this->file !== null && FLOW_SAPITYPE === 'CLI')) {
            $this->moved = $this->writeFile($targetPath);
        }

        if ($this->file !== null && FLOW_SAPITYPE !== 'CLI') {
            $this->moved = move_uploaded_file($this->file, $targetPath);
        }

        if ($this->moved === false) {
            throw new RuntimeException(sprintf('Uploaded file could not be moved to %s', $targetPath), 1479747889);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }

    /**
     * @throws RuntimeException if is moved or not ok
     */
    protected function throwExceptionIfNotAccessible()
    {
        if (!$this->isOk()) {
            throw new RuntimeException('UploadedFile has the following error: ' . Files::getUploadErrorMessage($this->error), 1479743608);
        }

        if ($this->isMoved()) {
            throw new RuntimeException('The uploaded file was moved already and cannot be accessed anymore.', 1479743612);
        }
    }

    /**
     * Write the uploaded file to the given path.
     *
     * @param string $path
     * @return boolean
     */
    protected function writeFile($path)
    {
        $handle = fopen($path, 'wb+');
        if ($handle === false) {
            return false;
        }

        $stream = $this->getStream();
        $stream->rewind();
        while (!$stream->eof()) {
            fwrite($handle, $stream->read(4096));
        }

        fclose($handle);
        return true;
    }
}
