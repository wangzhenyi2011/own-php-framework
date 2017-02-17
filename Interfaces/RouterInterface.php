<?php

namespace Baicaowei\Interfaces;

use RuntimeException;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{
    public function map($methods, $pattern, $handler);

    public function dispatch(ServerRequestInterface $request);

    public function pushGroup($pattern, $callable);

    public function popGroup();

    public function getNamedRoute($name);

    public function lookupRoute($identifier);

    public function relativePathFor($name, array $data = [], array $queryParams = []);

    public function pathFor($name, array $data = [], array $queryParams = []);
}
