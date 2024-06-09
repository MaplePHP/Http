<?php

declare(strict_types=1);

namespace MaplePHP\Http;

use RuntimeException;
use MaplePHP\Http\Interfaces\StreamInterface;

class Stream implements StreamInterface
{
    protected const DEFAULT_WRAPPER = 'php://temp';

    public const OUTPUT = 'php://output';
    public const INPUT = 'php://input';
    public const MEMORY = 'php://memory';
    public const TEMP = 'php://temp';
    public const FILTER = 'php://filter';
    public const STDIN = 'php://stdin';
    public const STDOUT = 'php://stdout';
    public const STDERR = 'php://stderr';
    public const FD = 'php://fd';

    private const READABLE_MATCH = '/r|a\+|ab\+|w\+|wb\+|x\+|xb\+|c\+|cb\+/';
    private const WRITABLE_MATCH = '/a|w|r\+|rb\+|rw|x|c/';

    private $stream;
    private $permission;
    private $resource;
    private $size;
    private $meta = array();
    private $readable;
    private $writable;
    private $seekable;

    /**
     * PSR-7 Stream
     * @param mixed  $stream
     * @param string    $permission Default stream permission is r+
     */
    public function __construct(mixed $stream = null, string $permission = "r+")
    {
        if (is_null($stream)) {
            $stream = $this::DEFAULT_WRAPPER;
        }

        if (is_resource($stream)) {
            $this->resource = $stream;
            $this->meta = $this->getMetadata();

            if (is_null($this->meta)) {
                throw new RuntimeException("Could not access the stream meta data.", 1);
            }

            $this->stream = $this->meta['stream_type'];
            $this->permission = $this->meta['mode'];
        } else {
            $this->stream = $stream;
            $this->permission = $permission;
            $this->resource = $this->fopen($this->stream, $this->permission);
            $this->meta = $this->getMetadata();
        }
    }

    /**
     * Get current stream
     * @return string
     */
    public function getStream(): string
    {
        return $this->stream;
    }

    /**
     * Get contents
     * @return string
     */
    public function __toString(): string
    {
        $this->rewind();
        return $this->getContents();
    }

    /**
     * Get current resource
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Closes the stream and any underlying resources.
     * @return void
     */
    public function close(): void
    {
        fclose($this->resource);
        $this->resource = null;
    }

    /**
     * Separates any underlying resources from the stream.
     * After the stream has been detached, the stream is in an unusable state.
     * @return null Underlying PHP stream, if any
     */
    public function detach()
    {
        $this->close();
        return $this->resource;
    }

    /**
     * Returns whether or not the stream is seekable.
     * @return bool
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * Returns whether or not the stream is writable.
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * Returns whether or not the stream is readable.
     * @return bool
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * Get stats
     * @param  string|null $key array item key of fstat
     * @return mixed
     */
    public function stats(?string $key = null): mixed
    {
        $stats = fstat($this->resource);
        if (is_array($stats)) {
            return is_null($key) ? $stats : ($stats[$key] ?? false);
        }
        return false;
    }

    /**
     * Get the size of the stream if known.
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize(): ?int
    {
        $size = $this->stats('size');
        $this->size = isset($size) ? $size : null;
        return $this->size;
    }

    /**
     * Returns the current position of the file read/write pointer
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell(): int
    {
        return (int)ftell($this->resource);
    }

    /**
     * Gets line from file pointer
     * @return string|false
     */
    public function getLine(): string|bool
    {
        $line = fgets($this->resource);
        return trim($line);
    }

    /**
     * Returns true if the stream is at the end of the stream.
     * @return bool
     */
    public function eof(): bool
    {
        return (is_resource($this->resource)) ? feof($this->resource) : true;
    }

    /**
     * Clean file
     * @return void
     */
    public function clean(): void
    {
        ftruncate($this->resource, 0);
    }

    /**
     * Seek to a position in the stream.
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if ($this->isSeekable()) {
            fseek($this->resource, $offset, $whence);
        } else {
            throw new RuntimeException("The stream \"{$this->stream} ({$this->permission})\" is not seekable!", 1);
        }
    }

    /**
     * Seek to the beginning of the stream.
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * Write data to the stream.
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     */
    public function write(string $string): int
    {
        if (is_null($this->size)) {
            $this->size = 0;
        }
        $byte = fwrite($this->resource, $string);
        return $byte;
    }

    /**
     * Read data from the stream.
     * @param int $length Read up to $length
     * @return string Returns the data read from the stream, or an empty string if no bytes are available.
     */
    public function read(int $length): string
    {
        if (!$this->isReadable() || ($body = fread($this->resource, $length)) === false) {
            throw new RuntimeException('Could not read from stream');
        }
        return $body;
    }

    /**
     * Returns the remaining contents in a string
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while reading.
     */
    public function getContents(): string
    {
        if (!$this->isReadable() || ($body = stream_get_contents($this->resource)) === false) {
            throw new RuntimeException('Could not get contents of stream');
        }
        return $body;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array
     */
    public function getMetadata(?string $key = null): mixed
    {
        $this->meta = stream_get_meta_data($this->resource);
        $this->readable = (bool)preg_match(self::READABLE_MATCH, $this->meta['mode']);
        $this->writable = (bool)preg_match(self::WRITABLE_MATCH, $this->meta['mode']);
        $this->seekable = $this->meta['seekable'];
        return (!is_null($key) ? ($this->meta[$key] ?? null) : $this->meta);
    }

    /**
     * Stream withContext
     * @param  array  $opts
     * @return StreamInterface
     */
    public function withContext(array $opts): StreamInterface
    {
        $inst = clone $this;
        $context = stream_context_create($opts);
        $inst->resource = $this->fopen($this->stream, $this->permission, false, $context);
        return $inst;
    }

    /**
     * Open a resource correct with the right resource
     * @param ...$fArgs
     * @return false|resource
     * @throws \RuntimeException on error.
     */
    private function fopen(...$fArgs)
    {
        set_error_handler(function ($errorNo, $errorStr) {
            throw new RuntimeException('Failed to open stream: ' . $errorStr, $errorNo);
        });
        try {
            $this->resource = fopen(...$fArgs);
        } finally {
            restore_error_handler();
        }
        return $this->resource;
    }
}
