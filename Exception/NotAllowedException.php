<?php
namespace Baicaowei\Exception;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class NotAllowedException extends BcwException
{
    protected $allowedMethods;

    public function __construct(ServerRequestInterface $request, ResponseInterface $response, array $allowedMethods)
    {
        parent::__construct($request, $response);
        $this->allowedMethods = $allowedMethods;
    }

    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }
}
