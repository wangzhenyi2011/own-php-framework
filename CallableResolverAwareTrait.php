<?php

namespace Baicaowei;

use RuntimeException;
use Interop\Container\ContainerInterface;
use Baicaowei\Interfaces\CallableResolverInterface;

trait CallableResolverAwareTrait
{
    protected function resolveCallable($callable)
    {
        if (!$this->container instanceof ContainerInterface) {
            return $callable;
        }

        $resolver = $this->container->get('callableResolver');

        return $resolver->resolve($callable);
    }
}
