<?php

namespace Baicaowei;

use ArrayIterator;
use Baicaowei\Interfaces\CollectionInterface;

class Collection implements CollectionInterface
{
    protected $data = [];


    public function __construct(array $items = [])
    {
        $this->replace($items);
    }


    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }


    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->data[$key] : $default;
    }


    public function replace(array $items)
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
    }


    public function all()
    {
        return $this->data;
    }


    public function keys()
    {
        return array_keys($this->data);
    }


    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }


    public function remove($key)
    {
        unset($this->data[$key]);
    }


    public function clear()
    {
        $this->data = [];
    }


    public function offsetExists($key)
    {
        return $this->has($key);
    }


    public function offsetGet($key)
    {
        return $this->get($key);
    }


    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }


    public function offsetUnset($key)
    {
        $this->remove($key);
    }


    public function count()
    {
        return count($this->data);
    }


    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }
}
