<?php
namespace SlimPhinx;

use SlimApi\Service\ConfigService;

class Module
{
    /**
     * load the dependencies for the module.
     */
    public function loadDependencies()
    {
        return ConfigService::fetch(dirname(__DIR__));
    }
}
