<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Resource\Streams;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A stream wrapper for package resources.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class PackageResourceStreamWrapper implements \F3\FLOW3\Resource\Streams\StreamWrapperInterface {

	/**
	 * @var resource
	 */
	public $context ;

	/**
	 * @var resource
	 */
	protected $handle;

	/**
	 * @var \F3\FLOW3\Property\DataType\Uri
	 */
	protected $uri;

	/**
	 * @var \F3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Injects a package manager.
	 *
	 * @param \F3\FLOW3\Package\PackageManagerInterface $packageManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectPackageManager(\F3\FLOW3\Package\PackageManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * Injects an object factory.
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Returns the scheme ("protocol") this wrapper handles.
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function getScheme() {
		return 'package';
	}

	/**
	 * Checks the given $path for use of the scheme this wrapper is intended for.
	 *
	 * @param string $path
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function checkScheme($path) {
		if (substr($path, 0, 7) !== self::getScheme()) {
			throw new \RuntimeException('The ' . __CLASS__ . ' only supports the \'' . self::getScheme() . '\' scheme.', 1256052544);
		}
	}

	/**
	 * Close directory handle.
	 *
	 * This method is called in response to closedir().
	 *
	 * Any resources which were locked, or allocated, during opening and use of
	 * the directory stream should be released.
	 *
	 * @return boolean TRUE on success or FALSE on failure.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function closeDirectory() {
		return closedir($this->handle);
	}

	/**
	 * Open directory handle.
	 *
	 * This method is called in response to opendir().
	 *
	 * @param string $path Specifies the URL that was passed to opendir().
	 * @param int $options Whether or not to enforce safe_mode (0x04).
	 * @return boolean TRUE on success or FALSE on failure.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function openDirectory($path, $options) {
		$this->checkScheme($path);

		$uri = $this->objectManager->create('F3\FLOW3\Property\DataType\Uri', $path);
		$package = $this->packageManager->getPackage($uri->getHost());
		$path = \F3\FLOW3\Utility\Files::concatenatePaths(array($package->getResourcesPath(), $uri->getPath()));
		$handle = opendir($path);
		if ($handle !== FALSE) {
			$this->handle = $handle;
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Read entry from directory handle.
	 *
	 * This method is called in response to readdir().
	 *
	 * @return string Should return string representing the next filename, or FALSE if there is no next file.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function readDirectory() {
		return readdir($this->handle);
	}

	/**
	 * Rewind directory handle.
	 *
	 * This method is called in response to rewinddir().
	 *
	 * Should reset the output generated by dir_readdir(). I.e.: The next call
	 * to dir_readdir() should return the first entry in the location returned
	 * by dir_opendir().
	 *
	 * @return boolean TRUE on success or FALSE on failure.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function rewindDirectory() {
		return rewinddir($this->handle);
	}

	/**
	 * Create a directory.
	 *
	 * This method is called in response to mkdir().
	 *
	 * @param string $path Directory which should be created.
	 * @param integer $mode The value passed to mkdir().
	 * @param integer $options A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function makeDirectory($path, $mode, $options) {
		$this->checkScheme($path);

		$uri = $this->objectManager->create('F3\FLOW3\Property\DataType\Uri', $path);
		$package = $this->packageManager->getPackage($uri->getHost());
		$path = \F3\FLOW3\Utility\Files::concatenatePaths(array($package->getResourcesPath(), $uri->getPath()));
		mkdir($path, $mode, $options & STREAM_MKDIR_RECURSIVE);
	}

	/**
	 * Removes a directory.
	 *
	 * This method is called in response to rmdir().
	 *
	 * Note: If the wrapper does not support creating directories it must throw
	 * a \BadMethodCallException.
	 *
	 * @param string $path The directory URL which should be removed.
	 * @param integer $options A bitwise mask of values, such as STREAM_MKDIR_RECURSIVE.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function removeDirectory($path, $options) {
		$this->checkScheme($path);

		throw new \BadMethodCallException('The package stream wrapper does not support rmdir.', 1256827649);
	}

	/**
	 * Renames a file or directory.
	 *
	 * This method is called in response to rename().
	 *
	 * Should attempt to rename path_from to path_to.
	 *
	 * @param string $source The URL to the current file.
	 * @param string $target The URL which the path_from should be renamed to.
	 * @return boolean TRUE on success or FALSE on failure.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function rename($source, $target) {
		return FALSE;
	}

	/**
	 * Retrieve the underlaying resource.
	 *
	 * This method is called in response to stream_select().
	 *
	 * @param integer $castType Can be STREAM_CAST_FOR_SELECT when stream_select() is calling stream_cast() or STREAM_CAST_AS_STREAM when stream_cast() is called for other uses.
	 * @return resource Should return the underlying stream resource used by the wrapper, or FALSE.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function cast($castType) {
		return FALSE;
	}

	/**
	 * Close an resource.
	 *
	 * This method is called in response to fclose().
	 *
	 * All resources that were locked, or allocated, by the wrapper should be
	 * released.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function close() {
		fclose($this->handle);
	}

	/**
	 * Tests for end-of-file on a file pointer.
	 *
	 * This method is called in response to feof().
	 *
	 * @return boolean Should return TRUE if the read/write position is at the end of the stream and if no more data is available to be read, or FALSE otherwise.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isAtEof() {
		return feof($this->handle);
	}

	/**
	 * Flushes the output.
	 *
	 * This method is called in response to fflush().
	 *
	 * If you have cached data in your stream but not yet stored it into the
	 * underlying storage, you should do so now.
	 *
	 * Note: If not implemented, FALSE is assumed as the return value.
	 *
	 * @return boolean Should return TRUE if the cached data was successfully stored (or if there was no data to store), or FALSE if the data could not be stored.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flush() {
		return TRUE;
	}

	/**
	 * Advisory file locking.
	 *
	 * This method is called in response to flock(), when file_put_contents()
	 * (when flags contains LOCK_EX), stream_set_blocking().
	 *
	 * $operation is one of the following:
	 *  LOCK_SH to acquire a shared lock (reader).
	 *  LOCK_EX to acquire an exclusive lock (writer).
	 *  LOCK_NB if you don't want flock() to block while locking.
	 *
	 * @param integer $operation One of the LOCK_* constants
	 * @return boolean TRUE on success or FALSE on failure.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function lock($operation) {
		return FALSE;
	}

	/**
	 * Advisory file locking.
	 *
	 * This method is called when closing the stream (LOCK_UN).
	 *
	 * @return boolean TRUE on success or FALSE on failure.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function unlock() {
		return TRUE;
	}

	/**
	 * Opens file or URL.
	 *
	 * This method is called immediately after the wrapper is initialized (f.e.
	 * by fopen() and file_get_contents()).
	 *
	 * $options can hold one of the following values OR'd together:
	 *  STREAM_USE_PATH
	 *    If path is relative, search for the resource using the include_path.
	 *  STREAM_REPORT_ERRORS
	 *    If this flag is set, you are responsible for raising errors using
	 *    trigger_error() during opening of the stream. If this flag is not set,
	 *    you should not raise any errors.
	 *
	 * @param string $path Specifies the URL that was passed to the original function.
	 * @param string $mode The mode used to open the file, as detailed for fopen().
	 * @param integer $options Holds additional flags set by the streams API.
	 * @param string &$openedPathAndFilename If the path is opened successfully, and STREAM_USE_PATH is set in options, opened_path should be set to the full path of the file/resource that was actually opened.
	 * @return boolean TRUE on success or FALSE on failure.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function open($path, $mode, $options, &$openedPathAndFilename) {
		$this->checkScheme($path);

		$uri = $this->objectManager->create('F3\FLOW3\Property\DataType\Uri', $path);
		$package = $this->packageManager->getPackage($uri->getHost());
		$pathAndFilename = \F3\FLOW3\Utility\Files::concatenatePaths(array($package->getResourcesPath(), $uri->getPath()));
		$this->handle = fopen($pathAndFilename, $mode);
		return (boolean) $this->handle;
	}

	/**
	 * Read from stream.
	 *
	 * This method is called in response to fread() and fgets().
	 *
	 * Note: Remember to update the read/write position of the stream (by the
	 * number of bytes that were successfully read).
	 *
	 * @param integer $count How many bytes of data from the current position should be returned.
	 * @return string If there are less than count bytes available, return as many as are available. If no more data is available, return either FALSE or an empty string.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function read($count) {
		return fread($this->handle, $count);
	}

	/**
	 * Seeks to specific location in a stream.
	 *
	 * This method is called in response to fseek().
	 *
	 * The read/write position of the stream should be updated according to the
	 * offset and whence .
	 *
	 * $whence can one of:
	 *  SEEK_SET - Set position equal to offset bytes.
	 *  SEEK_CUR - Set position to current location plus offset.
	 *  SEEK_END - Set position to end-of-file plus offset.
	 *
	 * @param integer $offset The stream offset to seek to.
	 * @param integer $whence
	 * @return boolean TRUE on success or FALSE on failure.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function seek($offset, $whence = SEEK_SET) {
		return fseek($this->handle, $offset, $whence);
	}

	/**
	 * Change stream options.
	 *
	 * This method is called to set options on the stream.
	 *
	 * $option can be one of:
	 *  STREAM_OPTION_BLOCKING (The method was called in response to stream_set_blocking())
	 *  STREAM_OPTION_READ_TIMEOUT (The method was called in response to stream_set_timeout())
	 *  STREAM_OPTION_WRITE_BUFFER (The method was called in response to stream_set_write_buffer())
	 *
	 * If $option is ... then $arg1 is set to:
	 *  STREAM_OPTION_BLOCKING: requested blocking mode (1 meaning block 0 not blocking).
	 *  STREAM_OPTION_READ_TIMEOUT: the timeout in seconds.
	 *  STREAM_OPTION_WRITE_BUFFER: buffer mode (STREAM_BUFFER_NONE or STREAM_BUFFER_FULL).
	 *
	 * If $option is ... then $arg2 is set to:
	 *  STREAM_OPTION_BLOCKING: This option is not set.
	 *  STREAM_OPTION_READ_TIMEOUT: the timeout in microseconds.
	 *  STREAM_OPTION_WRITE_BUFFER: the requested buffer size.
	 *
	 * @param integer $option
	 * @param integer $argument1
	 * @param integer $argument2
	 * @return boolean TRUE on success or FALSE on failure. If option is not implemented, FALSE should be returned.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setOption($option, $argument1, $argument2) {
		return FALSE;
	}

	/**
	 * Retrieve the current position of a stream.
	 *
	 * This method is called in response to ftell().
	 *
	 * @return int Should return the current position of the stream.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function tell() {
		return ftell($this->handle);
	}

	/**
	 * Write to stream.
	 *
	 * This method is called in response to fwrite().
	 *
	 * If there is not enough room in the underlying stream, store as much as
	 * possible.
	 *
	 * Note: Remember to update the current position of the stream by number of
	 * bytes that were successfully written.
	 *
	 * @param string $data Should be stored into the underlying stream.
	 * @return int Should return the number of bytes that were successfully stored, or 0 if none could be stored.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function write($data) {
		return fwrite($this->handle, $data);
	}

	/**
	 * Delete a file.
	 *
	 * This method is called in response to unlink().
	 *
	 * Note: In order for the appropriate error message to be returned this
	 * method should not be defined if the wrapper does not support removing
	 * files.
	 *
	 * @param string $path The file URL which should be deleted.
	 * @return boolean TRUE on success or FALSE on failure.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function unlink($path) {
		$this->checkScheme($path);

		throw new \BadMethodCallException('The package stream wrapper does not support unlink.', 1256052118);
	}

	/**
	 * Retrieve information about a file resource.
	 *
	 * This method is called in response to fstat().
	 *
	 * @return array See http://php.net/stat
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function resourceStat() {
		return fstat($this->handle);
	}

	/**
	 * Retrieve information about a file.
	 *
	 * This method is called in response to all stat() related functions.
	 *
	 * $flags can hold one or more of the following values OR'd together:
	 *  STREAM_URL_STAT_LINK
	 *     For resources with the ability to link to other resource (such as an
	 *     HTTP Location: forward, or a filesystem symlink). This flag specified
	 *     that only information about the link itself should be returned, not
	 *     the resource pointed to by the link. This flag is set in response to
	 *     calls to lstat(), is_link(), or filetype().
	 *  STREAM_URL_STAT_QUIET
	 *     If this flag is set, your wrapper should not raise any errors. If
	 *     this flag is not set, you are responsible for reporting errors using
	 *     the trigger_error() function during stating of the path.
	 *
	 * @param string $path The file path or URL to stat. Note that in the case of a URL, it must be a :// delimited URL. Other URL forms are not supported.
	 * @param integer $flags Holds additional flags set by the streams API.
	 * @return array Should return as many elements as stat() does. Unknown or unavailable values should be set to a rational value (usually 0).
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function pathStat($path, $flags) {
		$this->checkScheme($path);

		$uri = $this->objectManager->create('F3\FLOW3\Property\DataType\Uri', $path);
		$package = $this->packageManager->getPackage($uri->getHost());
		$path = \F3\FLOW3\Utility\Files::concatenatePaths(array($package->getResourcesPath(), $uri->getPath()));
		if (file_exists($path)) {
			return stat($path);
		} else {
			return FALSE;
		}
	}

}

?>