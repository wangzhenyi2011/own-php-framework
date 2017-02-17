<?php

namespace Baicaowei;

use Exception;
use Throwable;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Baicaowei\Exception\BcwException;
use Baicaowei\Handlers\Strategies\RequestResponse;
use Baicaowei\Interfaces\InvocationStrategyInterface;
use Baicaowei\Interfaces\RouteInterface;

class Route extends Routable implements RouteInterface
{
    use MiddlewareAwareTrait;


    protected $methods = [];


    protected $identifier;


    protected $name;


    protected $groups;

    private $finalized = false;


    protected $outputBuffering = 'append';


    protected $arguments = [];


    protected $callable;


    public function __construct($methods, $pattern, $callable, $groups = [], $identifier = 0)
    {
        $this->methods  = is_string($methods) ? [$methods] : $methods;
        $this->pattern  = $pattern;
        $this->callable = $callable;
        $this->groups   = $groups;
        $this->identifier = 'route' . $identifier;
    }


    public function finalize()
    {
        if ($this->finalized) {
            return;
        }

        $groupMiddleware = [];
        foreach ($this->getGroups() as $group) {
            $groupMiddleware = array_merge($group->getMiddleware(), $groupMiddleware);
        }

        $this->middleware = array_merge($this->middleware, $groupMiddleware);

        foreach ($this->getMiddleware() as $middleware) {
            $this->addMiddleware($middleware);
        }

        $this->finalized = true;
    }


    public function getCallable()
    {
        return $this->callable;
    }


    public function setCallable($callable)
    {
        $this->callable = $callable;
    }


    public function getMethods()
    {
        return $this->methods;
    }


    public function getGroups()
    {
        return $this->groups;
    }


    public function getName()
    {
        return $this->name;
    }


    public function getIdentifier()
    {
        return $this->identifier;
    }


    public function getOutputBuffering()
    {
        return $this->outputBuffering;
    }


    public function setOutputBuffering($mode)
    {
        if (!in_array($mode, [false, 'prepend', 'append'], true)) {
            throw new InvalidArgumentException('Unknown output buffering mode');
        }
        $this->outputBuffering = $mode;
    }


    public function setName($name)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Route name must be a string');
        }
        $this->name = $name;
        return $this;
    }


    public function setArgument($name, $value)
    {
        $this->arguments[$name] = $value;
        return $this;
    }


    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }


    public function getArguments()
    {
        return $this->arguments;
    }


    public function getArgument($name, $default = null)
    {
        if (array_key_exists($name, $this->arguments)) {
            return $this->arguments[$name];
        }
        return $default;
    }


    public function prepare(ServerRequestInterface $request, array $arguments)
    {
        foreach ($arguments as $k => $v) {
            $this->setArgument($k, $v);
        }
    }


    public function run(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->finalize();
        return $this->callMiddlewareStack($request, $response);
    }


    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->callable = $this->resolveCallable($this->callable);

        $handler = isset($this->container) ? $this->container->get('foundHandler') : new RequestResponse();

        if ($this->outputBuffering === false) {
            $newResponse = $handler($this->callable, $request, $response, $this->arguments);
        } else {
            try {
                ob_start();
                $newResponse = $handler($this->callable, $request, $response, $this->arguments);
                $output = ob_get_clean();
            // @codeCoverageIgnoreStart
            } catch (Throwable $e) {
                ob_end_clean();
                throw $e;
            // @codeCoverageIgnoreEnd
            } catch (Exception $e) {
                ob_end_clean();
                throw $e;
            }
        }

        if ($newResponse instanceof ResponseInterface) {
            $response = $newResponse;
        } elseif (is_string($newResponse)) {
            if ($response->getBody()->isWritable()) {
                $response->getBody()->write($newResponse);
            }
        }

        if (!empty($output) && $response->getBody()->isWritable()) {
            if ($this->outputBuffering === 'prepend') {
                $body = new Http\Body(fopen('php://temp', 'r+'));
                $body->write($output . $response->getBody());
                $response = $response->withBody($body);
            } elseif ($this->outputBuffering === 'append') {
                $response->getBody()->write($output);
            }
        }

        return $response;
    }
}
