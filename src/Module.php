<?php
namespace SlimPhinx;

use SlimApi\Service\ConfigService;

class Module
{
    public function loadDependencies($container)
    {
        return ConfigService::fetch(dirname(__DIR__));
    }
}
