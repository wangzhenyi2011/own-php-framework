<?php

namespace Baicaowei\Handlers\Strategies;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Baicaowei\Interfaces\InvocationStrategyInterface;

class RequestResponse implements InvocationStrategyInterface
{
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ) {
        foreach ($routeArguments as $k => $v) {
            $request = $request->withAttribute($k, $v);
        }

        return call_user_func($callable, $request, $response, $routeArguments);
    }
}
