<?php

namespace Baicaowei\Exception;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Exception是PHP 5.2异常类的基类
 * 异常类的核心我理解就是抛出异常，而不是发生错误终止
 * 解决异常通常有两个问题，1.对错误代码进行编码2.字符串来解析消息从而处理这两种不同的情况(英语)
 * 现在在SPL中有总共13个新的异常类型
 * 分为两类逻辑异常和运行时异常
 */

class BcwException extends Exception
{
    protected $request;


    protected $response;


    public function __construct(ServerRequestInterface $request, ResponseInterface $response)
    {
        parent::__construct();
        $this->request = $request;
        $this->response = $response;
    }


    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }
}
