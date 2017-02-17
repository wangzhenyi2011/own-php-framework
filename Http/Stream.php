<?php

namespace Baicaowei\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class Stream implements StreamInterface
{
    const FSTAT_MODE_S_IFIFO = 0010000;


    protected static $modes = [
        'readable' => ['r', 'r+', 'w+', 'a+', 'x+', 'c+'],
        'writable' => ['r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'],
    ];


    protected $stream;

    protected $meta;

    protected $readable;

    protected $writable;

    protected $seekable;

    protected $size;


    protected $isPipe;


    public function __construct($stream)
    {
        $this->attach($stream);
    }


    public function getMetadata($key = null)
    {
        $this->meta = stream_get_meta_data($this->stream);
        if (is_null($key) === true) {
            return $this->meta;
        }

        return isset($this->meta[$key]) ? $this->meta[$key] : null;
    }


    protected function isAttached()
    {
        return is_resource($this->stream);
    }


    protected function attach($newStream)
    {
        if (is_resource($newStream) === false) {
            throw new InvalidArgumentException(__METHOD__ . ' argument must be a valid PHP resource');
        }

        if ($this->isAttached() === true) {
            $this->detach();
        }

        $this->stream = $newStream;
    }


    public function detach()
    {
        $oldResource = $this->stream;
        $this->stream = null;
        $this->meta = null;
        $this->readable = null;
        $this->writable = null;
        $this->seekable = null;
        $this->size = null;
        $this->isPipe = null;

        return $oldResource;
    }


    public function __toString()
    {
        if (!$this->isAttached()) {
            return '';
        }

        try {
            $this->rewind();
            return $this->getContents();
        } catch (RuntimeException $e) {
            return '';
        }
    }


    public function close()
    {
        if ($this->isAttached() === true) {
            if ($this->isPipe()) {
                pclose($this->stream);
            } else {
                fclose($this->stream);
            }
        }

        $this->detach();
    }


    public function getSize()
    {
        if (!$this->size && $this->isAttached() === true) {
            $stats = fstat($this->stream);
            $this->size = isset($stats['size']) && !$this->isPipe() ? $stats['size'] : null;
        }

        return $this->size;
    }


    public function tell()
    {
        if (!$this->isAttached() || ($position = ftell($this->stream)) === false || $this->isPipe()) {
            throw new RuntimeException('Could not get the position of the pointer in stream');
        }

        return $position;
    }


    public function eof()
    {
        return $this->isAttached() ? feof($this->stream) : true;
    }


    public function isReadable()
    {
        if ($this->readable === null) {
            if ($this->isPipe()) {
                $this->readable = true;
            } else {
                $this->readable = false;
                if ($this->isAttached()) {
                    $meta = $this->getMetadata();
                    foreach (self::$modes['readable'] as $mode) {
                        if (strpos($meta['mode'], $mode) === 0) {
                            $this->readable = true;
                            break;
                        }
                    }
                }
            }
        }

        return $this->readable;
    }


    public function isWritable()
    {
        if ($this->writable === null) {
            $this->writable = false;
            if ($this->isAttached()) {
                $meta = $this->getMetadata();
                foreach (self::$modes['writable'] as $mode) {
                    if (strpos($meta['mode'], $mode) === 0) {
                        $this->writable = true;
                        break;
                    }
                }
            }
        }

        return $this->writable;
    }


    public function isSeekable()
    {
        if ($this->seekable === null) {
            $this->seekable = false;
            if ($this->isAttached()) {
                $meta = $this->getMetadata();
                $this->seekable = !$this->isPipe() && $meta['seekable'];
            }
        }

        return $this->seekable;
    }


    public function seek($offset, $whence = SEEK_SET)
    {
        // Note that fseek returns 0 on success!
        if (!$this->isSeekable() || fseek($this->stream, $offset, $whence) === -1) {
            throw new RuntimeException('Could not seek in stream');
        }
    }


    public function rewind()
    {
        if (!$this->isSeekable() || rewind($this->stream) === false) {
            throw new RuntimeException('Could not rewind stream');
        }
    }


    public function read($length)
    {
        if (!$this->isReadable() || ($data = fread($this->stream, $length)) === false) {
            throw new RuntimeException('Could not read from stream');
        }

        return $data;
    }


    public function write($string)
    {
        if (!$this->isWritable() || ($written = fwrite($this->stream, $string)) === false) {
            throw new RuntimeException('Could not write to stream');
        }

        $this->size = null;

        return $written;
    }


    public function getContents()
    {
        if (!$this->isReadable() || ($contents = stream_get_contents($this->stream)) === false) {
            throw new RuntimeException('Could not get contents of stream');
        }

        return $contents;
    }


    public function isPipe()
    {
        if ($this->isPipe === null) {
            $this->isPipe = false;
            if ($this->isAttached()) {
                $mode = fstat($this->stream)['mode'];
                $this->isPipe = ($mode & self::FSTAT_MODE_S_IFIFO) !== 0;
            }
        }

        return $this->isPipe;
    }
}
