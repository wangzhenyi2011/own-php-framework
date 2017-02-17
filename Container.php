<?php

namespace Baicaowei;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Pimple\Container as PimpleContainer;
use BaicaoweiBaicaowei\Exception\ContainerValueNotFoundException;
use Baicaowei\Exception\ContainerException as SlimContainerException;

class Container extends PimpleContainer implements ContainerInterface
{
    private $defaultSettings = [
        'httpVersion' => '1.1',
        'responseChunkSize' => 4096,
        'outputBuffering' => 'append',
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails' => false,
        'addContentLengthHeader' => true,
        'routerCacheFile' => false,
    ];


    public function __construct(array $values = [])
    {
        parent::__construct($values);

        $userSettings = isset($values['settings']) ? $values['settings'] : [];
        $this->registerDefaultServices($userSettings);
    }


    private function registerDefaultServices($userSettings)
    {
        $defaultSettings = $this->defaultSettings;

        $this['settings'] = function () use ($userSettings, $defaultSettings) {
            return new Collection(array_merge($defaultSettings, $userSettings));
        };

        $defaultProvider = new DefaultServicesProvider();
        $defaultProvider->register($this);
    }


    public function get($id)
    {
        if (!$this->offsetExists($id)) {
            throw new ContainerValueNotFoundException(sprintf('Identifier "%s" is not defined.', $id));
        }
        try {
            return $this->offsetGet($id);
        } catch (\InvalidArgumentException $exception) {
            if ($this->exceptionThrownByContainer($exception)) {
                throw new SlimContainerException(
                    sprintf('Container error while retrieving "%s"', $id),
                    null,
                    $exception
                );
            } else {
                throw $exception;
            }
        }
    }


    private function exceptionThrownByContainer(\InvalidArgumentException $exception)
    {
        $trace = $exception->getTrace()[0];

        return $trace['class'] === PimpleContainer::class && $trace['function'] === 'offsetGet';
    }


    public function has($id)
    {
        return $this->offsetExists($id);
    }


    public function __get($name)
    {
        return $this->get($name);
    }

    public function __isset($name)
    {
        return $this->has($name);
    }
}
