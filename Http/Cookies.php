<?php

namespace Baicaowei\Http;

use InvalidArgumentException;
use Baicaowei\Interfaces\Http\CookiesInterface;

class Cookies implements CookiesInterface
{
    protected $requestCookies = [];


    protected $responseCookies = [];


    protected $defaults = [
        'value' => '',
        'domain' => null,
        'hostonly' => null,
        'path' => null,
        'expires' => null,
        'secure' => false,
        'httponly' => false
    ];


    public function __construct(array $cookies = [])
    {
        $this->requestCookies = $cookies;
    }


    public function setDefaults(array $settings)
    {
        $this->defaults = array_replace($this->defaults, $settings);
    }

    /**
     * Get request cookie
     *
     * @param  string $name    Cookie name
     * @param  mixed  $default Cookie default value
     *
     * @return mixed Cookie value if present, else default
     */
    public function get($name, $default = null)
    {
        return isset($this->requestCookies[$name]) ? $this->requestCookies[$name] : $default;
    }


    public function set($name, $value)
    {
        if (!is_array($value)) {
            $value = ['value' => (string)$value];
        }
        $this->responseCookies[$name] = array_replace($this->defaults, $value);
    }


    public function toHeaders()
    {
        $headers = [];
        foreach ($this->responseCookies as $name => $properties) {
            $headers[] = $this->toHeader($name, $properties);
        }

        return $headers;
    }


    protected function toHeader($name, array $properties)
    {
        $result = urlencode($name) . '=' . urlencode($properties['value']);

        if (isset($properties['domain'])) {
            $result .= '; domain=' . $properties['domain'];
        }

        if (isset($properties['path'])) {
            $result .= '; path=' . $properties['path'];
        }

        if (isset($properties['expires'])) {
            if (is_string($properties['expires'])) {
                $timestamp = strtotime($properties['expires']);
            } else {
                $timestamp = (int)$properties['expires'];
            }
            if ($timestamp !== 0) {
                $result .= '; expires=' . gmdate('D, d-M-Y H:i:s e', $timestamp);
            }
        }

        if (isset($properties['secure']) && $properties['secure']) {
            $result .= '; secure';
        }

        if (isset($properties['hostonly']) && $properties['hostonly']) {
            $result .= '; HostOnly';
        }

        if (isset($properties['httponly']) && $properties['httponly']) {
            $result .= '; HttpOnly';
        }

        return $result;
    }


    public static function parseHeader($header)
    {
        if (is_array($header) === true) {
            $header = isset($header[0]) ? $header[0] : '';
        }

        if (is_string($header) === false) {
            throw new InvalidArgumentException('Cannot parse Cookie data. Header value must be a string.');
        }

        $header = rtrim($header, "\r\n");
        $pieces = preg_split('@[;]\s*@', $header);
        $cookies = [];

        foreach ($pieces as $cookie) {
            $cookie = explode('=', $cookie, 2);

            if (count($cookie) === 2) {
                $key = urldecode($cookie[0]);
                $value = urldecode($cookie[1]);

                if (!isset($cookies[$key])) {
                    $cookies[$key] = $value;
                }
            }
        }

        return $cookies;
    }
}
