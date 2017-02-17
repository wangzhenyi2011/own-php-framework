<?php

namespace Baicaowei;

use Interop\Container\ContainerInterface;

abstract class Routable
{
    use CallableResolverAwareTrait;


    protected $callable;


    protected $container;


    protected $middleware = [];


    protected $pattern;


    public function getMiddleware()
    {
        return $this->middleware;
    }


    public function getPattern()
    {
        return $this->pattern;
    }


    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        return $this;
    }


    public function add($callable)
    {
        $this->middleware[] = new DeferredCallable($callable, $this->container);
        return $this;
    }


    public function setPattern($newPattern)
    {
        $this->pattern = $newPattern;
    }
}
