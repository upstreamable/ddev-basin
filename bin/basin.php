#!/usr/bin/env php
<?php

include $_composer_autoload_path ?? __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Basin\Command\SyncAddons;
use Basin\Command\PostStartComposer;
use Basin\Command\PostStartHook;
use Basin\Command\PostStartAutoInstall;

$application = new Application('Basin');

$application->addCommands([
    new SyncAddons(),
    new PostStartHook(),
    new PostStartComposer(),
    new PostStartAutoInstall(),
]);

$statusCode = $application->run();

exit($statusCode);
