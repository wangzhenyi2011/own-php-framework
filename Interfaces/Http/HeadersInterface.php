<?php

namespace Baicaowei\Interfaces\Http;

use Baicaowei\Interfaces\CollectionInterface;

interface HeadersInterface extends CollectionInterface
{
    public function add($key, $value);

    public function normalizeKey($key);
}
