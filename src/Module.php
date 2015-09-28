<?php
namespace SlimPhinx;

class Module
{
    public function loadDependencies($container)
    {
        return SlimApi\Service\ConfigService::fetch(dirname(__DIR__));
    }
}
