<?php
namespace SlimPhinx;

class Module
{
    public function loadDependencies($container)
    {
        require __DIR__.'/dependencies.php';
    }
}
