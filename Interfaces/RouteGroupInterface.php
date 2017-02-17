<?php
namespace Baicaowei\Interfaces;

use Baicaowei\App;

interface RouteGroupInterface
{
    public function getPattern();

    public function add($callable);

    public function __invoke(App $app);
}
