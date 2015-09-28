<?php
$container['phinxApplication'] = function($container) {
    return new Phinx\Console\PhinxApplication;
};

$container['SlimApi\Database\DatabaseInterface'] = function($container) {
    return new SlimPhinx\Database\PhinxService($container->get('phinxApplication'));
};

$container['database.config.file'] = function($container) {
    $cwd = getcwd();
    $locator = new Symfony\Component\Config\FileLocator([$cwd . DIRECTORY_SEPARATOR]);
    return $locator->locate('phinx.yml', $cwd, true);
};

$container['database.config'] = function($container) {
    $configFilePath = $container['database.config.file'];
    $config = Phinx\Config\Config::fromYaml($configFilePath);
    return $config;
};
