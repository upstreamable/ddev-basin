#!/usr/bin/env php
<?php

include $_composer_autoload_path ?? __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

$container = new ContainerBuilder();

// Load container configuration
$loader = new YamlFileLoader($container, new FileLocator());

// Load other packages that might add basin commands.
foreach (glob(__DIR__ . '/../../*/basin-config/services.yaml') as $file) {
    $loader->load($file);
}

$loader->load(__DIR__ . '/config/services.yml');

$container->addCompilerPass(new AddConsoleCommandPass());

// Compile container
$container->compile();

// Start the console application.
$application = $container->get(Application::class);
exit($application->run());
