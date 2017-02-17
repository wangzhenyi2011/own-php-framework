<?php

namespace Baicaowei\Interfaces;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

interface RouteInterface
{
    public function getArgument($name, $default = null);

    public function getArguments();

    public function getName();

    public function getPattern();

    public function setArgument($name, $value);

    public function setArguments(array $arguments);

    public function setName($name);

    public function add($callable);

    public function prepare(ServerRequestInterface $request, array $arguments);

    public function run(ServerRequestInterface $request, ResponseInterface $response);

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response);
}
