#!/usr/bin/env php
<?php

include $_composer_autoload_path ?? __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Basin\Command\SyncAddons;

$application = new Application();

$application->addCommand(new SyncAddons());

$statusCode = $application->run();

exit($statusCode);
