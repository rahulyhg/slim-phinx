<?php
$config = [];
$config['phinxApplication'] = function($container) {
    return new Phinx\Console\PhinxApplication;
};

$config['SlimApi\Migration\MigrationInterface'] = function($container) {
    return new SlimApi\Phinx\Database\PhinxService($container->get('phinxApplication'));
};

$config['phinx.config.file'] = function($container) {
    $cwd     = getcwd();
    $locator = new Symfony\Component\Config\FileLocator([$cwd . DIRECTORY_SEPARATOR]);
    return $locator->locate('phinx.yml', $cwd, true);
};

$config['database.config'] = function($container) {
    $config             = $container['phinx.config'];
    $environment        = $container['environment.name'];
    $standardisedConfig = $container['phinx.config']->getEnvironment($environment);
    return $standardisedConfig;
};

$config['phinx.config'] = function($container) {
    $configFilePath = $container['phinx.config.file'];
    $config         = Phinx\Config\Config::fromYaml($configFilePath);
    return $config;
};

$config['SlimApi\Phinx\Init'] = function($container) {
};

return $config;
