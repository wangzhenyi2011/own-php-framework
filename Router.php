<?php

namespace Baicaowei;

use FastRoute\Dispatcher;
use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\RouteParser\Std as StdParser;
use Baicaowei\Interfaces\RouteGroupInterface;
use Baicaowei\Interfaces\RouterInterface;
use Baicaowei\Interfaces\RouteInterface;

class Router implements RouterInterface
{
    protected $container;


    protected $routeParser;


    protected $basePath = '';


    protected $cacheFile = false;


    protected $routes = [];


    protected $routeCounter = 0;


    protected $routeGroups = [];


    protected $dispatcher;


    public function __construct(RouteParser $parser = null)
    {
        $this->routeParser = $parser ?: new StdParser;
    }


    public function setBasePath($basePath)
    {
        if (!is_string($basePath)) {
            throw new InvalidArgumentException('Router basePath must be a string');
        }

        $this->basePath = $basePath;

        return $this;
    }


    public function setCacheFile($cacheFile)
    {
        if (!is_string($cacheFile) && $cacheFile !== false) {
            throw new InvalidArgumentException('Router cacheFile must be a string or false');
        }

        $this->cacheFile = $cacheFile;

        if ($cacheFile !== false && !is_writable(dirname($cacheFile))) {
            throw new RuntimeException('Router cacheFile directory must be writable');
        }


        return $this;
    }


    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function map($methods, $pattern, $handler)
    {
        if (!is_string($pattern)) {
            throw new InvalidArgumentException('Route pattern must be a string');
        }

        if ($this->routeGroups) {
            $pattern = $this->processGroups() . $pattern;
        }

        $methods = array_map("strtoupper", $methods);

        $route = $this->createRoute($methods, $pattern, $handler);
        $this->routes[$route->getIdentifier()] = $route;
        $this->routeCounter++;

        return $route;
    }


    public function dispatch(ServerRequestInterface $request)
    {
        $uri = '/' . ltrim($request->getUri()->getPath(), '/');

        return $this->createDispatcher()->dispatch(
            $request->getMethod(),
            $uri
        );
    }


    protected function createRoute($methods, $pattern, $callable)
    {
        $route = new Route($methods, $pattern, $callable, $this->routeGroups, $this->routeCounter);
        if (!empty($this->container)) {
            $route->setContainer($this->container);
        }

        return $route;
    }


    protected function createDispatcher()
    {
        if ($this->dispatcher) {
            return $this->dispatcher;
        }

        $routeDefinitionCallback = function (RouteCollector $r) {
            foreach ($this->getRoutes() as $route) {
                $r->addRoute($route->getMethods(), $route->getPattern(), $route->getIdentifier());
            }
        };

        if ($this->cacheFile) {
            $this->dispatcher = \FastRoute\cachedDispatcher($routeDefinitionCallback, [
                'routeParser' => $this->routeParser,
                'cacheFile' => $this->cacheFile,
            ]);
        } else {
            $this->dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback, [
                'routeParser' => $this->routeParser,
            ]);
        }

        return $this->dispatcher;
    }


    public function setDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }


    public function getRoutes()
    {
        return $this->routes;
    }


    public function getNamedRoute($name)
    {
        foreach ($this->routes as $route) {
            if ($name == $route->getName()) {
                return $route;
            }
        }
        throw new RuntimeException('Named route does not exist for name: ' . $name);
    }


    public function removeNamedRoute($name)
    {
        $route = $this->getNamedRoute($name);

        unset($this->routes[$route->getIdentifier()]);
    }


    protected function processGroups()
    {
        $pattern = "";
        foreach ($this->routeGroups as $group) {
            $pattern .= $group->getPattern();
        }
        return $pattern;
    }


    public function pushGroup($pattern, $callable)
    {
        $group = new RouteGroup($pattern, $callable);
        array_push($this->routeGroups, $group);
        return $group;
    }


    public function popGroup()
    {
        $group = array_pop($this->routeGroups);
        return $group instanceof RouteGroup ? $group : false;
    }


    public function lookupRoute($identifier)
    {
        if (!isset($this->routes[$identifier])) {
            throw new RuntimeException('Route not found, looks like your route cache is stale.');
        }
        return $this->routes[$identifier];
    }


    public function relativePathFor($name, array $data = [], array $queryParams = [])
    {
        $route = $this->getNamedRoute($name);
        $pattern = $route->getPattern();

        $routeDatas = $this->routeParser->parse($pattern);

        $routeDatas = array_reverse($routeDatas);

        $segments = [];
        foreach ($routeDatas as $routeData) {
            foreach ($routeData as $item) {
                if (is_string($item)) {
                    $segments[] = $item;
                    continue;
                }

                // This segment has a parameter: first element is the name
                if (!array_key_exists($item[0], $data)) {
                    $segments = [];
                    $segmentName = $item[0];
                    break;
                }
                $segments[] = $data[$item[0]];
            }
            if (!empty($segments)) {
                break;
            }
        }

        if (empty($segments)) {
            throw new InvalidArgumentException('Missing data for URL segment: ' . $segmentName);
        }
        $url = implode('', $segments);

        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }


    public function pathFor($name, array $data = [], array $queryParams = [])
    {
        $url = $this->relativePathFor($name, $data, $queryParams);

        if ($this->basePath) {
            $url = $this->basePath . $url;
        }

        return $url;
    }


    public function urlFor($name, array $data = [], array $queryParams = [])
    {
        trigger_error('urlFor() is deprecated. Use pathFor() instead.', E_USER_DEPRECATED);
        return $this->pathFor($name, $data, $queryParams);
    }
}
