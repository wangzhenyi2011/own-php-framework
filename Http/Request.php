<?php

namespace Baicaowei\Http;

use Closure;
use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Baicaowei\Collection;
use Baicaowei\Interfaces\Http\HeadersInterface;

class Request extends Message implements ServerRequestInterface
{
    protected $method;

    protected $originalMethod;

    protected $uri;

    protected $requestTarget;

    protected $queryParams;

    protected $cookies;

    protected $serverParams;

    protected $attributes;

    protected $bodyParsed = false;

    protected $bodyParsers = [];

    protected $uploadedFiles;

    protected $validMethods = [
        'CONNECT' => 1,
        'DELETE' => 1,
        'GET' => 1,
        'HEAD' => 1,
        'OPTIONS' => 1,
        'PATCH' => 1,
        'POST' => 1,
        'PUT' => 1,
        'TRACE' => 1,
    ];

    public static function createFromEnvironment(Environment $environment)
    {
        $method = $environment['REQUEST_METHOD'];
        $uri = Uri::createFromEnvironment($environment);
        $headers = Headers::createFromEnvironment($environment);
        $cookies = Cookies::parseHeader($headers->get('Cookie', []));
        $serverParams = $environment->all();
        $body = new RequestBody();
        $uploadedFiles = UploadedFile::createFromEnvironment($environment);

        $request = new static($method, $uri, $headers, $cookies, $serverParams, $body, $uploadedFiles);

        if ($method === 'POST' &&
            in_array($request->getMediaType(), ['application/x-www-form-urlencoded', 'multipart/form-data'])
        ) {
            // parsed body must be $_POST
            $request = $request->withParsedBody($_POST);
        }
        return $request;
    }

    public function __construct(
        $method,
        UriInterface $uri,
        HeadersInterface $headers,
        array $cookies,
        array $serverParams,
        StreamInterface $body,
        array $uploadedFiles = []
    ) {
        $this->originalMethod = $this->filterMethod($method);
        $this->uri = $uri;
        $this->headers = $headers;
        $this->cookies = $cookies;
        $this->serverParams = $serverParams;
        $this->attributes = new Collection();
        $this->body = $body;
        $this->uploadedFiles = $uploadedFiles;

        if (isset($serverParams['SERVER_PROTOCOL'])) {
            $this->protocolVersion = str_replace('HTTP/', '', $serverParams['SERVER_PROTOCOL']);
        }

        if (!$this->headers->has('Host') || $this->uri->getHost() !== '') {
            $this->headers->set('Host', $this->uri->getHost());
        }

        $this->registerMediaTypeParser('application/json', function ($input) {
            return json_decode($input, true);
        });

        $this->registerMediaTypeParser('application/xml', function ($input) {
            $backup = libxml_disable_entity_loader(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);
            return $result;
        });

        $this->registerMediaTypeParser('text/xml', function ($input) {
            $backup = libxml_disable_entity_loader(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);
            return $result;
        });

        $this->registerMediaTypeParser('application/x-www-form-urlencoded', function ($input) {
            parse_str($input, $data);
            return $data;
        });
    }


    public function __clone()
    {
        $this->headers = clone $this->headers;
        $this->attributes = clone $this->attributes;
        $this->body = clone $this->body;
    }


    public function getMethod()
    {
        if ($this->method === null) {
            $this->method = $this->originalMethod;
            $customMethod = $this->getHeaderLine('X-Http-Method-Override');

            if ($customMethod) {
                $this->method = $this->filterMethod($customMethod);
            } elseif ($this->originalMethod === 'POST') {
                $overrideMethod = $this->filterMethod($this->getParsedBodyParam('_METHOD'));
                if ($overrideMethod !== null) {
                    $this->method = $overrideMethod;
                }

                if ($this->getBody()->eof()) {
                    $this->getBody()->rewind();
                }
            }
        }

        return $this->method;
    }


    public function getOriginalMethod()
    {
        return $this->originalMethod;
    }


    public function withMethod($method)
    {
        $method = $this->filterMethod($method);
        $clone = clone $this;
        $clone->originalMethod = $method;
        $clone->method = $method;

        return $clone;
    }


    protected function filterMethod($method)
    {
        if ($method === null) {
            return $method;
        }

        if (!is_string($method)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method; must be a string, received %s',
                (is_object($method) ? get_class($method) : gettype($method))
            ));
        }

        $method = strtoupper($method);
        if (!isset($this->validMethods[$method])) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method "%s" provided',
                $method
            ));
        }

        return $method;
    }


    public function isMethod($method)
    {
        return $this->getMethod() === $method;
    }


    public function isGet()
    {
        return $this->isMethod('GET');
    }


    public function isPost()
    {
        return $this->isMethod('POST');
    }


    public function isPut()
    {
        return $this->isMethod('PUT');
    }


    public function isPatch()
    {
        return $this->isMethod('PATCH');
    }


    public function isDelete()
    {
        return $this->isMethod('DELETE');
    }


    public function isHead()
    {
        return $this->isMethod('HEAD');
    }


    public function isOptions()
    {
        return $this->isMethod('OPTIONS');
    }


    public function isXhr()
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }


    public function getRequestTarget()
    {
        if ($this->requestTarget) {
            return $this->requestTarget;
        }

        if ($this->uri === null) {
            return '/';
        }

        $basePath = $this->uri->getBasePath();
        $path = $this->uri->getPath();
        $path = $basePath . '/' . ltrim($path, '/');

        $query = $this->uri->getQuery();
        if ($query) {
            $path .= '?' . $query;
        }
        $this->requestTarget = $path;

        return $this->requestTarget;
    }


    public function withRequestTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException(
                'Invalid request target provided; must be a string and cannot contain whitespace'
            );
        }
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }


    public function getUri()
    {
        return $this->uri;
    }


    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $clone = clone $this;
        $clone->uri = $uri;

        if (!$preserveHost) {
            if ($uri->getHost() !== '') {
                $clone->headers->set('Host', $uri->getHost());
            }
        } else {
            if ($this->uri->getHost() !== '' && (!$this->hasHeader('Host') || $this->getHeader('Host') === null)) {
                $clone->headers->set('Host', $uri->getHost());
            }
        }

        return $clone;
    }


    public function getContentType()
    {
        $result = $this->getHeader('Content-Type');

        return $result ? $result[0] : null;
    }


    public function getMediaType()
    {
        $contentType = $this->getContentType();
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

            return strtolower($contentTypeParts[0]);
        }

        return null;
    }


    public function getMediaTypeParams()
    {
        $contentType = $this->getContentType();
        $contentTypeParams = [];
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);
            $contentTypePartsLength = count($contentTypeParts);
            for ($i = 1; $i < $contentTypePartsLength; $i++) {
                $paramParts = explode('=', $contentTypeParts[$i]);
                $contentTypeParams[strtolower($paramParts[0])] = $paramParts[1];
            }
        }

        return $contentTypeParams;
    }


    public function getContentCharset()
    {
        $mediaTypeParams = $this->getMediaTypeParams();
        if (isset($mediaTypeParams['charset'])) {
            return $mediaTypeParams['charset'];
        }

        return null;
    }


    public function getContentLength()
    {
        $result = $this->headers->get('Content-Length');

        return $result ? (int)$result[0] : null;
    }


    public function getCookieParams()
    {
        return $this->cookies;
    }


    public function getCookieParam($key, $default = null)
    {
        $cookies = $this->getCookieParams();
        $result = $default;
        if (isset($cookies[$key])) {
            $result = $cookies[$key];
        }

        return $result;
    }


    public function withCookieParams(array $cookies)
    {
        $clone = clone $this;
        $clone->cookies = $cookies;

        return $clone;
    }


    public function getQueryParams()
    {
        if (is_array($this->queryParams)) {
            return $this->queryParams;
        }

        if ($this->uri === null) {
            return [];
        }

        parse_str($this->uri->getQuery(), $this->queryParams); // <-- URL decodes data

        return $this->queryParams;
    }


    public function withQueryParams(array $query)
    {
        $clone = clone $this;
        $clone->queryParams = $query;

        return $clone;
    }


    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }


    public function withUploadedFiles(array $uploadedFiles)
    {
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;

        return $clone;
    }


    public function getServerParams()
    {
        return $this->serverParams;
    }


    public function getServerParam($key, $default = null)
    {
        $serverParams = $this->getServerParams();

        return isset($serverParams[$key]) ? $serverParams[$key] : $default;
    }


    public function getAttributes()
    {
        return $this->attributes->all();
    }


    public function getAttribute($name, $default = null)
    {
        return $this->attributes->get($name, $default);
    }

    /** 为什么用clone 为什么不用set()
        因为PSR-7的规则是HTTP Message和url都是值对象(value object)
        简单来说它的值决定了它的唯一性,如果两个值对象的所有值都是一样的，那它们也应该是同一个对象；
        反过来说，如果两个值中对象有至少一个值不一样，那它们就是两个不同的对象。
        所以要改变Request对象的一个属性时，你应该创建，而不能在旧的上修改。
        值对象是一种不可变的对象。
    **/

    public function withAttribute($name, $value)
    {
        $clone = clone $this;
        $clone->attributes->set($name, $value);

        return $clone;
    }


    public function withAttributes(array $attributes)
    {
        $clone = clone $this;
        $clone->attributes = new Collection($attributes);

        return $clone;
    }


    public function withoutAttribute($name)
    {
        $clone = clone $this;
        $clone->attributes->remove($name);

        return $clone;
    }


    public function getParsedBody()
    {
        if ($this->bodyParsed !== false) {
            return $this->bodyParsed;
        }

        if (!$this->body) {
            return null;
        }

        $mediaType = $this->getMediaType();

        $parts = explode('+', $mediaType);
        if (count($parts) >= 2) {
            $mediaType = 'application/' . $parts[count($parts)-1];
        }

        if (isset($this->bodyParsers[$mediaType]) === true) {
            $body = (string)$this->getBody();
            $parsed = $this->bodyParsers[$mediaType]($body);

            if (!is_null($parsed) && !is_object($parsed) && !is_array($parsed)) {
                throw new RuntimeException(
                    'Request body media type parser return value must be an array, an object, or null'
                );
            }
            $this->bodyParsed = $parsed;
            return $this->bodyParsed;
        }

        return null;
    }


    public function withParsedBody($data)
    {
        if (!is_null($data) && !is_object($data) && !is_array($data)) {
            throw new InvalidArgumentException('Parsed body value must be an array, an object, or null');
        }

        $clone = clone $this;
        $clone->bodyParsed = $data;

        return $clone;
    }


    public function reparseBody()
    {
        $this->bodyParsed = false;

        return $this;
    }


    public function registerMediaTypeParser($mediaType, callable $callable)
    {
        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this);
        }
        $this->bodyParsers[(string)$mediaType] = $callable;
    }


    public function getParam($key, $default = null)
    {
        $postParams = $this->getParsedBody();
        $getParams = $this->getQueryParams();
        $result = $default;
        if (is_array($postParams) && isset($postParams[$key])) {
            $result = $postParams[$key];
        } elseif (is_object($postParams) && property_exists($postParams, $key)) {
            $result = $postParams->$key;
        } elseif (isset($getParams[$key])) {
            $result = $getParams[$key];
        }

        return $result;
    }


    public function getParsedBodyParam($key, $default = null)
    {
        $postParams = $this->getParsedBody();
        $result = $default;
        if (is_array($postParams) && isset($postParams[$key])) {
            $result = $postParams[$key];
        } elseif (is_object($postParams) && property_exists($postParams, $key)) {
            $result = $postParams->$key;
        }

        return $result;
    }


    public function getQueryParam($key, $default = null)
    {
        $getParams = $this->getQueryParams();
        $result = $default;
        if (isset($getParams[$key])) {
            $result = $getParams[$key];
        }

        return $result;
    }


    public function getParams()
    {
        $params = $this->getQueryParams();
        $postParams = $this->getParsedBody();
        if ($postParams) {
            $params = array_merge($params, (array)$postParams);
        }

        return $params;
    }
}
