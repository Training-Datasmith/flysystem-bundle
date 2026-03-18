<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'secret' => 'test_secret',
        'test' => true,
        'http_method_override' => false,
    ]);
};
