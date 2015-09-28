<?php
$config = [];
$config['phinxApplication'] = function($container) {
    return new Phinx\Console\PhinxApplication;
};

$config['SlimApi\Database\DatabaseInterface'] = function($container) {
    return new SlimPhinx\Database\PhinxService($container->get('phinxApplication'));
};

$config['database.config.file'] = function($container) {
    $cwd = getcwd();
    $locator = new Symfony\Component\Config\FileLocator([$cwd . DIRECTORY_SEPARATOR]);
    return $locator->locate('phinx.yml', $cwd, true);
};

$config['database.config'] = function($container) {
    $configFilePath = $container['database.config.file'];
    $config = Phinx\Config\Config::fromYaml($configFilePath);
    return $config;
};

return $config;
