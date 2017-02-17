<?php

namespace Baicaowei\Exception;

use RuntimeException;
use Interop\Container\Exception\NotFoundException as InteropNotFoundException;

/**
 * RuntimeException 容器运行异常
 */
class ContainerValueNotFoundException extends RuntimeException implements InteropNotFoundException
{
}
