<?php

namespace Baicaowei\Http;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

abstract class Message implements MessageInterface
{
    protected $protocolVersion = '1.1';


    protected static $validProtocolVersions = [
        '1.0' => true,
        '1.1' => true,
        '2.0' => true,
    ];


    protected $headers;


    protected $body;


    public function __set($name, $value)
    {
    }


    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }


    public function withProtocolVersion($version)
    {
        if (!isset(self::$validProtocolVersions[$version])) {
            throw new InvalidArgumentException(
                'Invalid HTTP version. Must be one of: '
                . implode(', ', array_keys(self::$validProtocolVersions))
            );
        }
        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }


    public function getHeaders()
    {
        return $this->headers->all();
    }


    public function hasHeader($name)
    {
        return $this->headers->has($name);
    }


    public function getHeader($name)
    {
        return $this->headers->get($name, []);
    }


    public function getHeaderLine($name)
    {
        return implode(',', $this->headers->get($name, []));
    }


    public function withHeader($name, $value)
    {
        $clone = clone $this;
        $clone->headers->set($name, $value);

        return $clone;
    }


    public function withAddedHeader($name, $value)
    {
        $clone = clone $this;
        $clone->headers->add($name, $value);

        return $clone;
    }


    public function withoutHeader($name)
    {
        $clone = clone $this;
        $clone->headers->remove($name);

        return $clone;
    }


    public function getBody()
    {
        return $this->body;
    }


    public function withBody(StreamInterface $body)
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }
}
