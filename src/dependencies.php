<?php
$container['phinxApplication'] = function($container) {
    return new Phinx\Console\PhinxApplication;
};

$container['SlimApi\Database\DatabaseInterface'] = function($container) {
    return new SlimPhinx\Database\PhinxService($container->get('phinxApplication'));
};
