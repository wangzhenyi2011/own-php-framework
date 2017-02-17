<?php

namespace Baicaowei;

use RuntimeException;
use SplStack;
use SplDoublyLinkedList;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

/**
 * 以堆栈来做中间件，如果中间件有嵌套，后加先执行
 */
trait MiddlewareAwareTrait
{
    protected $stack;


    protected $middlewareLock = false;


    protected function addMiddleware(callable $callable)
    {
        if ($this->middlewareLock) {
            throw new RuntimeException('Middleware can’t be added once the stack is dequeuing');
        }

        if (is_null($this->stack)) {
            $this->seedMiddlewareStack();
        }
        $next = $this->stack->top();
        $this->stack[] = function (ServerRequestInterface $req, ResponseInterface $res) use ($callable, $next) {
            $result = call_user_func($callable, $req, $res, $next);
            if ($result instanceof ResponseInterface === false) {
                throw new UnexpectedValueException(
                    'Middleware must return instance of \Psr\Http\Message\ResponseInterface'
                );
            }

            return $result;
        };

        return $this;
    }


    protected function seedMiddlewareStack(callable $kernel = null)
    {
        if (!is_null($this->stack)) {
            throw new RuntimeException('MiddlewareStack can only be seeded once.');
        }
        if ($kernel === null) {
            $kernel = $this;
        }
        $this->stack = new SplStack;
        $this->stack->setIteratorMode(SplDoublyLinkedList::IT_MODE_LIFO | SplDoublyLinkedList::IT_MODE_KEEP);
        $this->stack[] = $kernel;
    }


    public function callMiddlewareStack(ServerRequestInterface $req, ResponseInterface $res)
    {
        if (is_null($this->stack)) {
            $this->seedMiddlewareStack();
        }
        $start = $this->stack->top();
        $this->middlewareLock = true;
        $resp = $start($req, $res);
        $this->middlewareLock = false;
        return $resp;
    }
}
