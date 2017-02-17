<?php

namespace Baicaowei\Handlers\Strategies;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Baicaowei\Interfaces\InvocationStrategyInterface;

class RequestResponseArgs implements InvocationStrategyInterface
{
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ) {
        array_unshift($routeArguments, $request, $response);

        return call_user_func_array($callable, $routeArguments);
    }
}
