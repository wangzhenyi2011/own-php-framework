<?php

namespace Baicaowei\Exception;

use InvalidArgumentException;
use Interop\Container\Exception\ContainerException as InteropContainerException;

/**
 * InvalidArgumentException 容器参数参数异常
 */
class ContainerException extends InvalidArgumentException implements InteropContainerException
{
}
