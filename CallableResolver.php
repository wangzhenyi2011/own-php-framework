<?php

namespace Baicaowei;

use RuntimeException;
use Interop\Container\ContainerInterface;
use Baicaowei\Interfaces\CallableResolverInterface;

final class CallableResolver implements CallableResolverInterface
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function resolve($toResolve)
    {
        $resolved = $toResolve;

        if (!is_callable($toResolve) && is_string($toResolve)) {
            $callablePattern = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';
            if (preg_match($callablePattern, $toResolve, $matches)) {
                $class = $matches[1];
                $method = $matches[2];

                if ($this->container->has($class)) {
                    $resolved = [$this->container->get($class), $method];
                } else {
                    if (!class_exists($class)) {
                        throw new RuntimeException(sprintf('Callable %s does not exist', $class));
                    }
                    $resolved = [new $class($this->container), $method];
                }
            } else {
                $class = $toResolve;
                if ($this->container->has($class)) {
                    $resolved = $this->container->get($class);
                } else {
                    if (!class_exists($class)) {
                        throw new RuntimeException(sprintf('Callable %s does not exist', $class));
                    }
                    $resolved = new $class($this->container);
                }
            }
        }

        if (!is_callable($resolved)) {
            throw new RuntimeException(sprintf(
                '%s is not resolvable',
                is_array($toResolve) || is_object($toResolve) ? json_encode($toResolve) : $toResolve
            ));
        }

        return $resolved;
    }
}
