<?php

declare(strict_types=1);

namespace MaplePHP\Http;

use RuntimeException;
use MaplePHP\Http\Interfaces\UploadedFileInterface;
use MaplePHP\Http\Interfaces\StreamInterface;

class UploadedFile implements UploadedFileInterface
{
    public const MOVE_CHUNK_SIZE = 1024;

    public const ERROR_PHRASE = [
        UPLOAD_ERR_OK => "There is no error, the file uploaded with success",
        UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
        UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified " .
        "in the HTML form",
        UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
        UPLOAD_ERR_NO_FILE => "No file was uploaded",
        UPLOAD_ERR_NO_TMP_DIR => "No temporary directory. This is a server error, please try again later",
        UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
        UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload"
    ];

    private $stream;
    private $size;
    private $name;
    private $type;
    private $tmp;
    private $error;
    private $moved;
    //private $target;

    /**
     * Prepare for upload
     * Expects: $_FILES['uploadKey']
     *          StreamInterface
     *          (string) FilePath/php stream
     */
    public function __construct($stream)
    {
        if ($stream instanceof StreamInterface) {
            $this->stream = $stream;
        } elseif (isset($stream['tmp_name'])) {
            $this->name = $stream['name'];
            $this->type = $stream['type'];
            $this->tmp = $stream['tmp_name'];
            $this->error = $stream['error'];
            $this->size = $stream['size'];
        } elseif (is_string($stream)) {
            $this->stream = $this->withStream($stream);
        } else {
            throw new RuntimeException("The stream argument is not a valid resource", 1);
        }
    }

    /**
     * Get current stream if it exsists
     * @return StreamInterface
     */
    public function getStream(): StreamInterface
    {
        if (is_null($this->stream)) {
            throw new RuntimeException("The no stream exists. You need to construct a new stream", 1);
        }
        if (is_string($this->stream)) {
            $this->stream = $this->withStream($this->stream);
        }
        return $this->stream;
    }

    /**
     * Move file/output to target path/file. Will throw RuntimeException on error
     * @param  string $targetPath
     * @return void
     */
    public function moveTo($targetPath): void
    {
        if ($this->moved) {
            throw new RuntimeException('File has already been moved');
        }
        if (!is_writable(dirname($targetPath))) {
            throw new RuntimeException('Target directory is not writable');
        }

        if (!is_null($this->stream)) {
            $this->streamFile($targetPath);
        } elseif (!is_null($this->tmp)) {
            $this->moveUploadedFile($targetPath);
        }

        if (!$this->moved) {
            throw new RuntimeException('Failed to move file to target path');
        }
    }

    /**
     * Stream file/output to target path/file
     * @param  string $targetPath
     * @return void
     */
    public function streamFile(string $targetPath): void
    {
        $stream = $this->getStream();
        $stream->seek(0);

        $targetStream = new Stream($targetPath, 'w');
        while (!$stream->eof()) {
            $targetStream->write($stream->read($this::MOVE_CHUNK_SIZE));
        }

        // Add file to stream so it is changeable after upload
        $this->stream = $targetStream;
        $this->moved = true;
    }

    /**
     * Will be useing the PHP function move_uploaded_file to upload a file to target path/file
     * @param  string $targetPath
     * @return int|bool
     */
    public function moveUploadedFile(string $targetPath)
    {
        if (!is_uploaded_file($this->tmp)) {
            throw new RuntimeException("Could not upload the file \"{$this->name}\" becouse of a conflict.");
        }
        $this->moved = move_uploaded_file($this->tmp, $targetPath);

        // Add file to stream as a String, that way it will be prepared and
        // without loading resource unless you call/follow up with the @getStream method
        if ($this->moved) {
            $this->stream = $targetPath;
        }
        return $this->moved;
    }

    /**
     * Get file size
     * @return int|null
     */
    public function getSize(): ?int
    {
        return (is_null($this->size)) ? (($this->stream instanceof StreamInterface) ? $this->stream->getSize() : null) : $this->size;
    }

    /**
     * Get error
     * @return int
     */
    public function getError()
    {
        return (int)$this->error;
    }

    /**
     * Get error phrarse
     * @return string
     */
    public function getErrorPhrase(): ?string
    {
        return ($this::ERROR_PHRASE[$this->error] ?? null);
    }

    /**
     * Get client file name
     * @return string
     */
    public function getClientFilename(): ?string
    {
        return $this->name;
    }

    /**
     * Get client media type
     * @return string
     */
    public function getClientMediaType(): ?string
    {
        return $this->type;
    }

    public function withStream(string $stream): StreamInterface
    {
        $inst = new Stream($stream, 'r+');
        return $inst;
    }
}
