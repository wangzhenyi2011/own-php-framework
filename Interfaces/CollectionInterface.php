<?php

namespace Baicaowei\Interfaces;

/**
 * php 预定义四大接口(IteratorAggregate（聚合式aggregate迭代器Iterator）、Countable、ArrayAccess、Iterator)
 * Countable 统计对象的数量
 * ArrayAccess 数组方式访问对象
 * IteratorAggregate 对象数组可以进行forecha
 */

interface CollectionInterface extends \ArrayAccess, \Countable, \IteratorAggregate
{
    public function set($key, $value);

    public function get($key, $default = null);

    public function replace(array $items);

    public function all();

    public function has($key);

    public function remove($key);

    public function clear();
}
