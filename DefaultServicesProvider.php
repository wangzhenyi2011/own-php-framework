<?php

namespace Baicaowei;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Baicaowei\Handlers\PhpError;
use Baicaowei\Handlers\Error;
use Baicaowei\Handlers\NotFound;
use Baicaowei\Handlers\NotAllowed;
use Baicaowei\Handlers\Strategies\RequestResponse;
use Baicaowei\Http\Environment;
use Baicaowei\Http\Headers;
use Baicaowei\Http\Request;
use Baicaowei\Http\Response;
use Baicaowei\Interfaces\CallableResolverInterface;
use Baicaowei\Interfaces\Http\EnvironmentInterface;
use Baicaowei\Interfaces\InvocationStrategyInterface;
use Baicaowei\Interfaces\RouterInterface;

class DefaultServicesProvider
{
    public function register($container)
    {
        if (!isset($container['environment'])) {
            $container['environment'] = function () {
                return new Environment($_SERVER);
            };
        }

        if (!isset($container['request'])) {
            $container['request'] = function ($container) {
                return Request::createFromEnvironment($container->get('environment'));
            };
        }

        if (!isset($container['response'])) {
            $container['response'] = function ($container) {
                $headers = new Headers(['Content-Type' => 'text/html; charset=UTF-8']);
                $response = new Response(200, $headers);

                return $response->withProtocolVersion($container->get('settings')['httpVersion']);
            };
        }

        if (!isset($container['router'])) {
            $container['router'] = function ($container) {
                $routerCacheFile = false;
                if (isset($container->get('settings')['routerCacheFile'])) {
                    $routerCacheFile = $container->get('settings')['routerCacheFile'];
                }


                $router = (new Router)->setCacheFile($routerCacheFile);
                if (method_exists($router, 'setContainer')) {
                    $router->setContainer($container);
                }

                return $router;
            };
        }

        if (!isset($container['foundHandler'])) {
            $container['foundHandler'] = function () {
                return new RequestResponse;
            };
        }

        if (!isset($container['phpErrorHandler'])) {
            $container['phpErrorHandler'] = function ($container) {
                return new PhpError($container->get('settings')['displayErrorDetails']);
            };
        }

        if (!isset($container['errorHandler'])) {
            $container['errorHandler'] = function ($container) {
                return new Error($container->get('settings')['displayErrorDetails']);
            };
        }

        if (!isset($container['notFoundHandler'])) {
            $container['notFoundHandler'] = function () {
                return new NotFound;
            };
        }

        if (!isset($container['notAllowedHandler'])) {
            $container['notAllowedHandler'] = function () {
                return new NotAllowed;
            };
        }

        if (!isset($container['callableResolver'])) {
            $container['callableResolver'] = function ($container) {
                return new CallableResolver($container);
            };
        }
    }
}
